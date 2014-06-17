<?php

include 'APIBittrex.php';

function print_r2($val){
	echo '<pre>';
	print_r($val);
	echo  '</pre>';
}

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

/* Coin to sell */
$coin = 'FRAC';

/* Market where sell */
$market = 'BTC-FRAC';

/* min quantity to sell */ 
$min = 1.0;

/* the type of ticket to sell */
$ticket = 'Ask';

/* loops you want to wait open order before cancel them */
$min_loops = 5;


/**
 * Log file
 */

$filelog = "autosell.log";
$fh = fopen($filelog, 'a') or die("can't open file");

/**
 * Autosell
 */
$api = new APIBittrex($key, $apisecret);

$loop = 0;
$uuid = "";


$result = $api->get_balances();
print_r($result);
exit(0);

for(;;)
{			
	/* get balance */
	$result = $api->get_balance($coin);
	$amount = $result['result']['Available'];

	/* get ticker */
	$result = $api->get_ticker($market);
	$rate = $result['result'][$ticket];
	
	// Autosell
	if($amount > $min)
	{
		print "Sell limit $amount of $coin with rate $rate\n";
		
		$result = $api->sell_limit($market, $amount, $rate);		
		
		if ($result['success'])
		{
			/* save order */
			$uuid = $result['result']['uuid'];
			
			fwrite($fh, date('Y-m-d H:i:s', time()).": Sell market $amount $coin at $rate\n");
		}
		else
		{
			fwrite($fh, date('Y-m-d H:i:s', time()).": Error sell market: ".$result['message']."\n");
		}
	}
	
	$loop++;
	
	/* view open orders */
	$result = $api->get_open_orders($market);	
	
	/* check if our order sell still is open order */	
	foreach($result['result'] as $v){
		if( ($loop > $min_loops) && ($v['OrderUuid'] == $uuid) )
		{
			/* cancel order */
			$result = $api->cancel_order($uuid);
			if($result['success'])
			{
				fwrite($fh, date('Y-m-d H:i:s', time()).": Order $uuid canceled.\n");
			}
		}
	}
	
	/* confirm sell in history */
	if(empty($result['result']))
	{		
		$result = $api->get_order_history($market);
		foreach($result['result'] as $v){
			if($v['OrderUuid'] == $uuid){
				fwrite($fh, date('Y-m-d H:i:s', time()).": Selled order $uuid with ".$v['Quantity']." $coin at rate ".$v['Limit']." at ".$v['TimeStamp']."\n");
			}
		}
	}
		
	sleep(60);
}
