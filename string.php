<?php

$statement = "the stupid boy is a hero, and he in fucking crazily insane";

$words = explode(' ', $statement);
$word = '';

$len = sizeof($words);

if ($len % 2 != 0) {
    $word = array_pop($words);
    $len--;
}

for ($i = 0; $i < $len/2; $i++) {
    $wd = strlen($words[$i]) > strlen($words[$len - 1 - $i]) ? $words[$i] : $words[$len - 1 - $i];
    $word = strlen($wd) > strlen($word) ? $wd : $word;
}

echo $word;