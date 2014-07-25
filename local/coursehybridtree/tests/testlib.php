<?php

define('CLI_SCRIPT', true);
require_once __DIR__ . '/../locallib.php';

/**
 * Compare two values, using an operator '==' or a function($expected, $tested).
 *
 * @param mixed $expected
 * @param string|callable $cmp
 * @param mixed $tested
 * @param string $msg
 */
function ok($expected, $cmp, $tested, $msg) {
    if (is_callable($cmp)) {
        $test = call_user_func($cmp, $expected, $tested);
    } else {
        eval("\$test = \$expected $cmp \$tested;");
    }
    if ($test) {
        echo " \033[32m[X]\033[0m $msg : $expected\n";
    } else {
        die(
                " \033[31m*** $msg ERROR\033[0m"
                . "\n\t\033[31mExpected:\033[0m « " . print_r($expected, true) . " »" . (is_string($cmp) ? " $cmp" : '')
                . "\n\t\033[31mResult:\033[0m   « " . print_r($tested, true) . " »\n"
        );
    }
}

/**
 * Test if the expected value is a member of the iterable tested value.
 *
 * @param mixed $expected
 * @param string|callable $cmp
 * @param mixed $tested
 * @param string $msg
 */
function ok_contains($expected, $cmp, array $tested, $msg) {
    $success = false;
    foreach ($tested as $value) {
        if (is_callable($cmp)) {
            $test = call_user_func($cmp, $expected, $value);
        } else {
            eval("\$test = \$expected $cmp \$value;");
        }
        if ($test) {
            $success = true;
            break;
        }
    }
    if ($success) {
        echo " \033[32m[X]\033[0m $msg : $expected\n";
    } else {
        die(
                " \033[31m*** $msg ERROR\033[0m"
                . "\n\t\033[31mExpected contains:\033[0m « " . print_r($expected, true) . " »"
                . "\n\t\033[31mResult:\033[0m            « " . print_r($tested, true) . " »\n"
        );
    }
}