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

	protected $tld;
	protected $wordsFile = null;
    protected $curl;

	public function __construct($tld) {
		$this->tld = $tld;

        if (defined('static::USE_CURL')) {
            $this->curlInit();
        }

		$this->wordsFile = __DIR__."/../../words/" . $tld;
	}

	public function getWords()
	{
		$words = file_get_contents($this->wordsFile);
		$words = explode("\n", $words);

		foreach ($words as &$word) {
			$word = preg_replace("/".$this->tld."$/",".".$this->tld, $word);
		}

		return $words;
	}

    public function isSaved($domain)
    {
        if(Domain::where('domain','=',$domain)->count() > 0) {
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

    protected function presenter($domain, $registered, $whois) {
        return (object) array('domain' => $domain, 'registered' => $registered, 'whois' => $whois);
    }

    public function whois($domain)
    {
        $cmd = 'whois';

        if (isset($this->whoisServer)) {
            $cmd .= ' -h '.escapeshellarg($this->whoisServer);
        }

        $whois = shell_exec($cmd . ' '.escapeshellarg($domain));

        $registered = $this->isRegistered($whois);

        if (self::DEBUG) {
            echo $whois;
        }

        // Save to DB
        $this->save($domain, $registered, $whois);

        return $this->presenter($domain, $registered, $whois);
    }

    protected function curlInit()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
    }

    abstract protected function isRegistered($whois);
}

