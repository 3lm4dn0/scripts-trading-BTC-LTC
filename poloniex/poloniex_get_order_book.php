<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 3){
    echo "usage ".$argv[0]." MARKET NUMBER\n";
    echo "\texample: ".$argv[0]." BTC_ETH 10\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $total = $argv[2];
 
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

foreach($result as $type => $orders) {
    if(is_array($orders)){
        if($type == "asks") {
            foreach($orders as $value2)
                $asks[] = array('amount' => $value2[1], 'rate' => $value2[0]);
        }else{
            foreach($orders as $value2)
                $bids[] = array('amount' => $value2[1], 'rate' => $value2[0]);
        }
    }
}

echo "\n";
echo "Sell orders:\n";
foreach($asks as $value){
    printf("\tamount: %12.8f\trate: %12.8f\n", $value['amount'], $value['rate']);
}
echo "\n\n";

echo "Buy orders:\n";
foreach($bids as $value){
    printf("\tamount: %12.8f\trate: %12.8f\n", $value['amount'], $value['rate']);
}