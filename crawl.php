<?php

require_once __DIR__."/bootstrap.php";

if (!isset($argv[1])) {
    echo "No TLD specified!\n";
    exit;
}

$tld = strtolower($argv[1]);
$nicClass = "\Deft\Nic\\".ucfirst($tld);

if (!class_exists($nicClass)) {
    echo "Invalid TLD or implementation not created!\n";
    exit;
}

$nic = new $nicClass($tld);

$words = $nic->getWords();

echo "Total: ".count($words)."\n";

foreach ($words as $word) {
    if (!trim($word)) continue;

    if (!$nic->isSaved($word)) {
	    $whois = $nic->whois($word);

        echo $word;

	    if ($whois->registered === false) {
    		echo " IS NOT REGISTERED\n";
        } else {
            echo " IS REGISTERED\n";
        }
    } else {
        echo "Skipping ".$word."\n";
    }
}

