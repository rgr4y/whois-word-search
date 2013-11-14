<?php

$tlds = file_get_contents("tlds.txt");
$tlds = explode("\n", $tlds);

$dict = file_get_contents("/usr/share/dict/words");
$dict = explode("\n", $dict);

foreach ($dict as &$word) {
	$word = strtolower($word);
}

$dict = array_unique($dict);

foreach ($tlds as $tld) {
	$words = array();

	foreach ($dict as $word) {
		if (preg_match("/".$tld."$/", $word)) {
			echo $tld.": ".$word."\n";
			$words[] = $word;
		}
	}

	if (count($words)) {
		$words = implode("\n", $words);
		file_put_contents("words/".$tld, $words);
	}
}
