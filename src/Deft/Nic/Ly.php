<?php

namespace Deft\Nic;

/**
 * .LY TLD
 *
 * @package Deft\Nic
 * @author Rob Vella <me@robvella.com>
 */
class Ly extends \Deft\Nic {
	public function __construct($tld)
	{
		parent::__construct($tld);

        $this->curlInit();
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_URL, "http://nic.ly/whois.php");
	}

	protected function crawl($domain)
	{
		$domain = preg_replace("/\.ly$/","", $domain);
		$post = array('Submit' => 'Search', 'domain' => $domain);

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);
		return curl_exec($this->curl);
	}

    protected function isRegistered($whois)
    {
        return trim($whois) === "Domain not registered." ? true : false;
    }

	public function whois($domain)
	{
		$html = $this->crawl($domain);

		if (preg_match("/\<textarea.*\>(.*)\<\/textarea\>/s", $html, $matches)) {
			$whois = $matches[1];

            $registered = $this->isRegistered($whois);

            $this->save($domain, $registered, $whois);
            return $this->presenter($domain, $registered, $whois);
		} else {
			return false;
		}
	}
}
