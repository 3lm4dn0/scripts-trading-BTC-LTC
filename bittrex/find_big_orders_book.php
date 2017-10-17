<?php

include 'APIBittrex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 5){
    echo "usage ".$argv[0]." MARKET [SELL | BUY] MIN_AMOUNT TOTAL_ROWS\n";
    echo "\texample: ".$argv[0]." BTC_ETH BUY 1000 10\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $buy = $argv[2] == "BUY" ? true : false;
    $min_amount = $argv[3];
    $rows = $argv[4];
    $total = 10000;
 
    echo "Market: ". $pair;
    echo "\n";
}

/**
 * PRIVATE API
 */
$api = new APIBittrex("", "");

$type = $buy ? 'buy' : 'sell';
$result = $api->get_order_book($pair, $type, $total);
if(!($result['success'])){
    print_r($result);
    exit(-1);
}

if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

$orders = array();
$result = $result['result'];
foreach($result as $order) {    
    if($order['Quantity'] >= $min_amount){
        $orders[] = array('amount' => $order['Quantity'], 'rate' => $order['Rate']);
    }
}

echo "\n";
if($buy){
    echo "Buy orders:\n";
}else{
    echo "Sell orders:\n";
}
$orders = array_slice($orders, 0, $rows);
foreach($orders as $value){
    printf("\tamount: %12.8f\trate: %12.8f\n", $value['amount'], $value['rate']);
}
