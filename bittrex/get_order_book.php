<?php

include 'APIBittrex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 3){
    echo "usage ".$argv[0]." MARKET NUMBER\n";
    echo "\texample: ".$argv[0]." BTC-ETH 10\n";
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
$api = new APIBittrex("", "");

$result = $api->get_order_book($pair, 'sell', $total);
if(!($result['success'])){
    print_r($result);
    exit(-1);
}

if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}


foreach($result['result'] as $type => $orders) {
print_r($type);
print_r($orders);
exit(9);
    if(is_array($orders)){
        if($type == "sell") {
            foreach($orders as $value2)
                $bids[] = array('amount' => $value2[1], 'rate' => $value2[0]);            
        }else{
            foreach($orders as $value2)
                $asks[] = array('amount' => $value2[1], 'rate' => $value2[0]);
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
