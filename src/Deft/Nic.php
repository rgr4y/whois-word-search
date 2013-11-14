<?php

namespace Deft;

use Deft\Models\Domain;

/**
 * TLD/Nic base class
 *
 * @package Deft
 * @author Rob Vella <me@robvella.com>
 */
abstract class Nic
{
    const DEBUG = false;
    const TIMEOUT = 5;
    const SLEEP_INC = 5;

    protected $sleep = 0;
    protected $tld;
    protected $wordsFile = null;
    protected $curl;

    public function __construct($tld)
    {
        $this->tld = $tld;

        if (defined('static::USE_CURL')) {
            $this->curlInit();
        }

        $this->wordsDir  = __DIR__ . "/../../words/";
        $this->wordsFile = $this->wordsDir . $tld;
    }

    public function getWords()
    {
        $words = file_get_contents($this->wordsFile);
        $words = explode("\n", $words);

        foreach ($words as $k => &$word) {
            if ($word === $this->tld) unset($words[$k]);

            $word = preg_replace("/" . $this->tld . "$/", "." . $this->tld, $word);

            if (defined('static::REGEX')) {
                if (!preg_match(static::REGEX, $word)) {
                    echo "INVALID: " . $word."\n";
                    unset($words[$k]);
                }
            }
        }

        if (file_exists($this->wordsDir."all")) {
            $all = file_get_contents($this->wordsDir."all");
            $all = explode("\n", $all);

            foreach ($all as &$word) {
                if (empty($word)) continue;
                $words[] = $word . "." . $this->tld;
            }
        }

        return $words;
    }

    public function isSaved($domain)
    {
        if (Domain::where('domain', '=', $domain)->count() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function save($domain, $registered, $whois)
    {
        $model = new Domain;
        $model->tld = $this->tld;
        $model->domain = $domain;
        $model->registered = $registered;
        $model->whois = $whois;

        $model->save();
    }

    protected function presenter($domain, $registered, $whois)
    {
        return (object)array('domain' => $domain, 'registered' => $registered, 'whois' => $whois);
    }

    protected function rateLimit($domain)
    {
        $this->sleep += self::SLEEP_INC;

        echo "Sleeping ".$this->sleep." seconds due to rate limiting...\n";
        sleep($this->sleep);

        $whois = $this->runWhois($domain);

        return $whois;
    }

    protected function runWhois($domain)
    {
        $cmd = 'whois';

        if (isset($this->whoisServer)) {
            $cmd .= ' -h ' . escapeshellarg($this->whoisServer);
        }

        $cmd .= " " . escapeshellarg($domain);

        $whois = $this->exec_timeout($cmd, self::TIMEOUT);

        if (preg_match("/queries per hour exceeded/", $whois)) {
            $this->sleep = 3600;

            $whois = $this->rateLimit($domain);
        }

        // Rate limit so we don't get banned
        sleep(mt_rand(1,3));

        return $whois;
    }

    public function whois($domain)
    {
        $whois = $this->runWhois($domain);

        if (empty($whois)) {
            throw new \Exception('No whois data?');
        }

        $registered = $this->isRegistered($whois);

        // Save to DB
        $this->save($domain, $registered, $whois);

        return $this->presenter($domain, $registered, $whois);
    }

    protected function curlInit()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
    }

    /**
     * Execute a command and return it's output. Either wait until the command exits or the timeout has expired.
     *
     * @param string $cmd     Command to execute.
     * @param number $timeout Timeout in seconds.
     * @return string Output of the command.
     * @throws \Exception
     */
    protected function exec_timeout($cmd, $timeout)
    {
        // File descriptors passed to the process.
        $descriptors = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w') // stderr
        );

        // Start the process.
        $process = proc_open('exec ' . $cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new \Exception('Could not execute process');
        }

        // Set the stdout stream to none-blocking.
        stream_set_blocking($pipes[1], 0);

        // Turn the timeout into microseconds.
        $timeout = $timeout * 1000000;

        // Output buffer.
        $buffer = '';

        // While we have time to wait.
        while ($timeout > 0) {
            $start = microtime(true);

            // Wait until we have output or the timer expired.
            $read = array($pipes[1]);
            $other = array();
            stream_select($read, $other, $other, 0, $timeout);

            // Get the status of the process.
            // Do this before we read from the stream,
            // this way we can't lose the last bit of output if the process dies between these functions.
            $status = proc_get_status($process);

            // Read the contents from the buffer.
            // This function will always return immediately as the stream is none-blocking.
            $buffer .= stream_get_contents($pipes[1]);

            if (!$status['running']) {
                // Break from this loop if the process exited before the timeout.
                break;
            }

            // Subtract the number of microseconds that we waited.
            $timeout -= (microtime(true) - $start) * 1000000;
        }

        // Check if there were any errors.
        $errors = stream_get_contents($pipes[2]);

        if (!empty($errors)) {
            throw new \Exception($errors);
        }

        // Kill the process in case the timeout expired and it's still running.
        // If the process already exited this won't do anything.
        proc_terminate($process, 9);

        // Close all streams.
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        return $buffer;
    }

    /**
     * Determine if domain is registered
     *
     * @param $whois
     * @return bool
     */
    protected function isRegistered($whois)
    {
        $available = [
            'do not have an entry',
            'available for purchase',
            'Created On',
            'NOT FOUND',
            'No entries found for domain',
            'Domain not registered'
        ];

        $unavailable = [
            'Status : Live',
            'Domain reserved',
            'STATUS:BANNED',
            'Creation Date',
            'Created On',
        ];

        $available = "/" . implode("|", $available) . "/";
        $unavailable = "/" . implode("|", $unavailable) . "/";

        if (preg_match($unavailable, $whois)) {
            return true;
        } elseif (preg_match($available, $whois)) {
            return false;
        } else {
            throw new \Exception("Unknown WHOIS data\n" . $whois);
        }
    }
}

