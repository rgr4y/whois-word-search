<?php

namespace Deft\Nic;

/**
 * .LY TLD
 *
 * @package Deft\Nic
 * @author Rob Vella <me@robvella.com>
 */
class Ly extends \Deft\Nic {
    /**
     * Class constructor
     * 
     * @param $tld
     */
    public function __construct($tld)
	{
		parent::__construct($tld);

        $this->curlInit();
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_URL, "http://nic.ly/whois.php");
	}

    /**
     * Run CURL crawl
     *
     * @param $domain
     * @return mixed
     */
    protected function crawl($domain)
	{
		$domain = preg_replace("/\.ly$/","", $domain);
		$post = array('Submit' => 'Search', 'domain' => $domain);

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);
		return curl_exec($this->curl);
	}

    /**
     * Determine if domain is registered
     *
     * @param $whois
     * @return bool
     */
    protected function isRegistered($whois)
    {
        return trim($whois) === "Domain not registered." ? true : false;
    }

    /**
     * runWhois
     *
     * @param $domain
     * @return bool|string
     */
    public function runWhois($domain)
	{
		$html = $this->crawl($domain);

		if (preg_match("/\<textarea.*\>(.*)\<\/textarea\>/s", $html, $matches)) {
			$whois = $matches[1];

            return $whois;
		} else {
			return false;
		}
	}
}
