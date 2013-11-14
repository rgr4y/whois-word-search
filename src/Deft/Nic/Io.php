<?php

namespace Deft\Nic;

class Io extends \Deft\Nic
{
    protected function isRegistered($whois)
    {
        return preg_match("/available for purchase/", $whois) ? false : true;
    }
}
