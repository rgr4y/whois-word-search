<?php

require_once __DIR__."/bootstrap.php";

$words = $nic->getWords();

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
