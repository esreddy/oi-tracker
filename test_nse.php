<?php
// test_nse.php

$url = 'https://www.nseindia.com/api/option-chain-indices?symbol=NIFTY';
$cookieFile = __DIR__ . '/nse_test_cookie.txt';

$headers = [
    'Host: www.nseindia.com',
    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) ' .
    'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
    'Accept: application/json, text/plain, */*',
    'Accept-Language: en-US,en;q=0.9',
    'Accept-Encoding: gzip, deflate, br',
    'Referer: https://www.nseindia.com/option-chain',
    'Connection: keep-alive',
    'DNT: 1',
    'X-Requested-With: XMLHttpRequest',
    'Sec-Fetch-Site: same-origin',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Dest: empty',
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_ENCODING       => '',
    CURLOPT_CONNECTTIMEOUT => 20,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_USERAGENT      => $headers[1],
    CURLOPT_COOKIEJAR      => $cookieFile,
    CURLOPT_COOKIEFILE     => $cookieFile,

    // TEMP: disable verify just to see if PHP's CA bundle is the problem
    CURLOPT_SSL_VERIFYPEER => false,

    // Verbose debug
    CURLOPT_VERBOSE        => true,
]);

$out  = curl_exec($ch);
$info = curl_getinfo($ch);
$err  = curl_error($ch);

curl_close($ch);

echo "HTTP CODE: {$info['http_code']}\n";
echo "CURL ERROR: $err\n\n";
echo "FIRST 300 BYTES:\n";
echo substr((string)$out, 0, 300), "\n";
