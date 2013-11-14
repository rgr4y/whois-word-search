<?php

namespace Deft\Nic;

/**
 * .IO TLD
 *
 * @package Deft\Nic
 * @author Rob Vella <me@robvella.com>
 */
class Io extends \Deft\Nic
{
    protected function isRegistered($whois)
    {
        return preg_match("/available for purchase/", $whois) ? false : true;
    }
}
