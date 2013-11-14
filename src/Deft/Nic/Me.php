<?php

namespace Deft\Nic;

/**
 * .ME TLD
 *
 * @package Deft\Nic
 * @author Rob Vella <me@robvella.com>
 */
class Me extends \Deft\Nic
{
    /**
     * Determine if domain is registered
     *
     * @param $whois
     * @return bool
     */
    protected function isRegistered($whois)
    {
        if (preg_match("/Domain Create/", $whois)) {
            return true;
        } elseif (preg_match("/NOT FOUND/", $whois)) {
            return false;
        } else {
            throw new \Exception("Unknown WHOIS data\n" . $whois);
        }
    }
}
