<?php

include 'APIBittrex.php';

/**
 * Set timezone
 */
$timezone = 'Europe/Madrid';
date_default_timezone_set($timezone);

/**
 * Configure 
 */
$key = "";
$apisecret = "";

/* Coin to autowithdraw */
$coin = 'BTC';
$address = "";

/* min quantity to autowithdraw */ 
$min = 0.001;

/**
 * Log file
 */
$filelog = "bittrex.log";
$fh = fopen($filelog, 'a') or die("can't open file");

/**
 * Autowithdrawall
 */
$api = new APIBittrex($key, $apisecret);

for(;;)
{			
	/* get balance */
	$result = $api->get_balance($coin);
	$amount = $result['result']['Available'];
	
	// Withdrawal
	if($amount >= $min)
	{		
		$result = $api->withdraw($coin, $amount, $address);		
		
		if ($result['success'])
		{
			/* save order */
			$uuid = $result['result']['uuid'];
			
			fwrite($fh, date('Y-m-d H:i:s', time()).": Withdraw $amount from $coin to $address.\n");
		}
		else
		{
			fwrite($fh, date('Y-m-d H:i:s', time()).": Error withdraw: ".$result['message']."\n");
		}
	}	
		
	sleep(60);
}
