<?php

namespace Deft\Nic;

/**
 * .La TLD
 *
 * @package Deft\Nic
 * @author Rob Vella <me@robvella.com>
 */
class La extends \Deft\Nic
{
    protected function runWhois($domain)
    {
        $whois = parent::runWhois($domain);

        if (preg_match("/queries per hour exceeded/", $whois)) {
            $this->sleep = 3600;

            $whois = $this->rateLimit($domain);
        }

        return $whois;
    }

    /**
     * Determine if domain is registered
     *
     * @param $whois
     * @return bool
     */
    protected function isRegistered($whois)
    {
        if (preg_match("/Created On/", $whois)) {
            return true;
        } elseif (preg_match("/NOT FOUND|Status:BANNED/", $whois)) {
            return false;
        } else {
            throw new \Exception("Unknown WHOIS data\n" . $whois);
        }
    }
}
