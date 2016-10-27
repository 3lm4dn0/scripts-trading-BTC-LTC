<?php

include 'APIBittrex.php';

function print_r2($val){
	echo '<pre>';
	print_r($val);
	echo  '</pre>';
}

$key = getenv('BTT_KEY');
$apisecret = getenv('BTT_PASS');

$api = new APIBittrex($key, $apisecret);

/**
 * Public API
 */

$coin = 'BTC';
$address = getenv('MY_BTC_ADDRESS');
$min = 0.01;

/* get balance */
$result = $api->get_balance($coin);
$amount = $result['result']['Available'];

// Autosell
if($amount >= $min){
    $result = $api->withdraw($coin, $amount, $address);
    print_r2($result);
}

