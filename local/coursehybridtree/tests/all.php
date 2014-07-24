<?php
$files = glob(__DIR__ . '/*Test.php');

foreach ($files as $f) {
	$name = preg_replace('/Test.php$/', '', basename($f));
	echo "\n$name\n" . str_repeat("=", strlen($name)) . "\n";
	require $f;
}
