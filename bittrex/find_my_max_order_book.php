<?php

include 'APIBittrex.php';

function print_r2($val){
	print_r($val);
}

$key = getenv('BTT_KEY');
$apisecret = getenv('BTT_PASS');

$api = new APIBittrex($key, $apisecret);

/**
 * Public API
 */

$LIMIT = 600;
$TOTAL = 10;

for(;;){
    $result = $api->get_order_book('BTC-ETH', 'sell', $TOTAL);
    $sell = array_slice($result['result'], 0, $TOTAL);
    //if($sell[0]['Rate'] > 0.029)
    {
        print_r2($sell[0]);
        sleep(10);
    }
}
// 
$result = $api->get_order_book('BTC-ETH', 'both', $TOTAL);

$sell = array_slice($result['result']['sell'], 0, $TOTAL);
$buy = array_slice($result['result']['buy'], 0, $TOTAL);

echo "Maximum $LIMIT to buy (BID orders): \n";
$total_buy = 0;
foreach($buy as $k => $value){
    $total_buy += $value['Quantity'];
    if($value['Quantity'] > $LIMIT){
        echo "$k: ";
        print_r2($value);
    }
}


echo "Maximum $LIMIT to sell (ASK orders): \n";
$total_sell = 0;
foreach($sell as $k => $value){
    $total_sell += $value['Quantity'];
    if($value['Quantity'] > $LIMIT){
        echo "$k: ";
        print_r2($value);
    }
}

// print_r
echo "to buy $TOTAL orders: ". $total_buy . "\n";
echo "to sell $TOTAL orders: " . $total_sell . "\n";
