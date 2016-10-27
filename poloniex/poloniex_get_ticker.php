<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 2){
    echo "usage ".$argv[0]." MARKET\n";
    echo "\texample: ".$argv[0]." BTC_ETH\n";
    exit(-1);
}else{
    $pair = $argv[1];
 
    echo "Market: ". $pair;
    echo "\n";
}


/**
 * PRIVATE API
 */
$api = new APIPoloniex("", "");

$result = $api->get_ticker($pair);
if(isset($result['error'])){
    print_r($result);
    exit(-1);
}

if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

print_r($result);
