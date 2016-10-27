<?php

include 'APIPoloniex.php';

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
$api = new APIPoloniex("", "");

$result = $api->get_order_book($pair, $total);
if(isset($result['error'])){
    print_r($result);
    exit(-1);
}

if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

$bids = $asks = array();
foreach($result as $type => $orders) {
    if(is_array($orders)){
        if($type == "asks") {
            foreach($orders as $value2){
                if($value2[1] > $min_amount){
                    $asks[] = array('amount' => $value2[1], 'rate' => $value2[0]);
                }
            }
        }else{
            foreach($orders as $value2){
                if($value2[1] > $min_amount){
                    $bids[] = array('amount' => $value2[1], 'rate' => $value2[0]);
                }
            }
        }
    }
}

echo "\n";
if($buy){
    echo "Buy orders:\n";
    $bids = array_slice($bids, 0, $rows);
    foreach($bids as $value){
        printf("\tamount: %12.8f\trate: %12.8f\n", $value['amount'], $value['rate']);
    }
}else{
    echo "Sell orders:\n";
    $asks = array_slice($asks, 0, $rows);
    foreach($asks as $value){
        printf("\tamount: %12.8f\trate: %12.8f\n", $value['amount'], $value['rate']);
    }
}
