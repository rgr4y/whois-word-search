<?php

namespace Deft\Nic;

/**
 * .CH TLD
 *
 * @package Deft\Nic
 * @author Rob Vella <me@robvella.com>
 */
class Ch extends \Deft\Nic {
    const URL = "http://whois.europeregistry.com/whoisengine/request/whoisinfo.php?security_code=null&domain_name=";
    const USE_CURL = true;

    protected function isRegistered($whois)
    {
        return preg_match("/do not have an entry/", $whois) ? false : true;
    }

    public function whois($domain)
    {
        curl_setopt($this->curl, CURLOPT_URL, self::URL . $domain);

        $whois = curl_exec($this->curl);

        $registered = $this->isRegistered($whois);

        $this->save($domain, $registered, $whois);

        return $this->presenter($domain, $registered, $whois);
    }
}