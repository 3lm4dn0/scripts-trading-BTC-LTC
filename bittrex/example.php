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

/* get markets */
// $result = $api->get_markets();
// print_r2($result);

/**
 * Account API
 */

/* get balances @deprecated */
$result = $api->get_balances();
print_r2($result);

/* get withdrawal history */
// $result = $api->get_withdrawal_history('BTC');
// print_r2($result);
