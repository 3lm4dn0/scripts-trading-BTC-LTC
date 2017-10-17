<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
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

$key = getenv('POL_KEY');
$apisecret = getenv('POL_PASS');
if(empty($key) || empty($apisecret)){
    echo "Set environment variables from Exchange API Key:\n";
    echo "export POL_KEY=\"your key\"\n";
    echo "export POL_PASS=\"your super big secret\"\n";
    echo "\n\nPlease be careful with this sensible data. Store your keys encrypted, protect your profile and allow only connections from your IP and do not allow withdrawals.\n";
    exit(-1);
}

/**
 * PRIVATE API
 */
$api = new APIPoloniex($key, $apisecret);

if($orderNumber == "all"){
    $result = $api->get_open_orders($pair);
    foreach($result as $k => $value){
        $result = $api->cancel_order($pair, $value['orderNumber']);
        if(isset($result['error'])){
            print_r($result);
            exit(-1);
        }
        if(empty($result)) {
            echo "Empty data.\n";
            exit(-1);
        }
        print_r($result);
    }
}else {
    $result = $api->cancel_order($pair, $orderNumber);
    if (isset($result['error'])) {
        print_r($result);
        exit(-1);
    }
    if (empty($result)) {
        echo "Empty data.\n";
        exit(-1);
    }
    print_r($result);
}