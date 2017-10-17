<?php

include 'APIBittrex.php';

function print_r2($val){
    print_r($val);
    echo "\n";
}

if(count($argv) != 3){
    echo "usage ".$argv[0]." MARKET orderNumber\n";
    echo "\texample: ".$argv[0]." BTC_ETH all\n";
    echo "\texample: ".$argv[0]." BTC_ETH 300309211\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $orderNumber = $argv[2];

    echo "Pair: ". $pair;
    echo "\n";
    echo "orderNumber: ". $orderNumber;
    echo "\n";
}

$key = getenv('BIT_KEY');
$apisecret = getenv('BIT_PASS');
if(empty($key) || empty($apisecret)){
    echo "Set environment variables from Exchange API Key:\n";
    echo "export BIT_KEY=\"your key\"\n";
    echo "export BIT_PASS=\"your super big secret\"\n";
    echo "\n\nPlease be careful with this sensible data. Store your keys encrypted, protect your profile and allow only connections from your IP and do not allow withdrawals.\n";
    exit(-1);
}

/**
 * PRIVATE API
 */
$api = new APIBittrex($key, $apisecret);

if($orderNumber == "all"){
    $result = $api->get_open_orders($pair);
    foreach($result['result'] as $value){
        echo "Cancel order ".$value['OrderUuid']."\n";
        $result = $api->cancel_order($value['OrderUuid']);
        print_r($result);
    }
}else {
    $result = $api->cancel_order($orderNumber);
    print_r($result);
}
