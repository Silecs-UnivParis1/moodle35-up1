<?php

define('CLI_SCRIPT', true);
require_once __DIR__ . '/../locallib.php';

function ok($expected, $cmp, $b, $msg) {
    if (is_callable($cmp)) {
        $test = call_user_func($cmp, $expected, $b);
    } else {
        eval("\$test = \$expected $cmp \$b;");
    }
    if ($test) {
        echo " [X] $msg : $expected\n";
    } else {
        die(
                " *** $msg ERROR"
                . "\n\tExpected: " . print_r($expected, true)
                . "\n\tResult: [" . print_r($b, true) . "]\n"
        );
    }
}
