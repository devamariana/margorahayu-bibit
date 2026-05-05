<?php
$curl_options = [
    10023 => ['A', 'B', 'C']
];
$config = [
    10023 => ['X']
];
$headers = [
    10023 => ['A', 'B', 'C', 'X']
];

$res = array_replace_recursive($curl_options, $config, $headers);
var_export($res);
