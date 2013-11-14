<?php

namespace Deft\Nic;

/**
 * .To TLD
 *
 * @package Deft\Nic
 * @author Rob Vella <me@robvella.com>
 */
class To extends \Deft\Nic {
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
	}

    /**
     * Determine if domain is registered
     *
     * @param $whois
     * @return bool
     */
    protected function isRegistered($whois)
    {
        if (preg_match("/So sorry, that one's taken|^Available$/", $whois)) {
            return false;
        } else if (preg_match("/Your name .*? is available|^Not available$/", $whois)) {
            return true;
        } else {
            throw new \Exception("Invalid WHOIS data\n".$whois);
        }
    }

    /**
     * runWhois
     *
     * @param $domain
     * @return bool|string
     */
    public function runWhois($domain)
	{
        $form = file_get_contents("http://www.tonic.to/newname.htm");
        if (preg_match("/<form action=\"(.*?)\"/", $form, $matches)) {
            $url = $matches[1];

            curl_setopt($this->curl, CURLOPT_URL, $url);

            $domain = preg_replace("/\.".$this->tld."$/","", $domain);

            $post = array(
                'B1.x' => 63,
                'B1.y' => 29,
                'command' => 'findandhold',
                'B1' => 'Submit',
                'error' => 'nametaken.htm',
                'sld' => $domain
            );

            $post = http_build_query($post);

            curl_setopt($this->curl, CURLOPT_REFERER, "http://www.tonic.to/newname.htm");
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);

            $whois = curl_exec($this->curl);

            if (preg_match("/Excessive query rate/", $whois)) {
                $this->rateLimit($domain);
            }

            if ($this->isRegistered($whois)) {
                $whois = "Available";
            } else {
                $whois = "Not available";
            }

            // This site doesn't like high query rates :)
            sleep(mt_rand(1,3));
            return $whois;
        } else {
            return false;
        }
	}
}
