<?php

include 'APIBittrex.php';

function print_r2($val){
	echo '<pre>';
	print_r($val);
	echo  '</pre>';
}

$key = "";
$apisecret = "";

$api = new APIBittrex($key, $apisecret);

/**
 * Public API
 */

/**
 * Account API
 */

/* get balances @deprecated */
$result = $api->get_order_history('BTC-ETH');
print_r2($result);
