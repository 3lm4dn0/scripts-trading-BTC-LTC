<?php

include 'APIBittrex.php';

function extract_btc_values($api, $result){
    $array_btc = array();
    foreach($result['result'] as $value) {
        $balance = (double)$value['Balance'];
        $coin = $value['Currency'];
        
        if ($balance > 0.0) {
        
            if($coin != "BTC"){
                $price = get_sell_price($api, "BTC-".$coin);
                $btc_available = bcmul(sprintf("%01.8f", $value['Balance']), $price, 8);
            }else{
                $btc_available = $value['Balance'];
            }

            $array_btc[$coin] = $btc_available;
        }
    }
    
    return $array_btc;
}

function get_sell_price($api, $pair){
   
    /* get last market */
    $result = $api->get_ticker($pair);
    if(isset($result['error'])){
        print_r($result);
        exit(-1);
    }
    if(empty($result)) {
        echo "Empty data.\n";
        exit(-1);
    }
    
    return sprintf("%01.8f", $result['result']['Bid']);
}

function print_r2($val){
    print_r($val);
}

$request_btc_amount = (isset($argv[1]) && $argv[1] == "btc");

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
$result = $api->get_balances();
if(!$result['success']) {
    print_r($result);
    exit(-1);
}

if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

echo "Your balances\n";

if($request_btc_amount){
    $array_btc = extract_btc_values($api, $result);
}    

$btc_total = 0.0;
foreach($result['result'] as $value) {
    $balance = (double)$value['Balance'];
    $coin = $value['Currency'];
    
    if ($balance > 0.0) { 
        $btc_amount = $request_btc_amount ? $array_btc[$coin] : 0.0;
        printf("%s: \tbalance: %08.8f\tavailable: %08.8f\tpending: %08.8f\tBTC: %08.8f\n", 
            $coin, $value['Balance'], $value['Available'], $value['Pending'], $btc_amount);
        $btc_total += $btc_amount;
    }
}

if($request_btc_amount){
    echo "\tTotal Available BTC: " . $btc_total . "\n";
}
