<?php

include 'APIBittrex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 5 && count($argv) != 6){
    echo "usage ".$argv[0]." MARKET [BUY | SELL] [ AMOUNT | \"all\" ] RATE\n";
    echo "\texample: ".$argv[0]." BTC_ETH BUY 20.55 0.024\n";
    echo "\texample: ".$argv[0]." BTC_ETH SELL all 0.033\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $type = $argv[2];
    $amount = $argv[3];
    $rate = $argv[4];
    $no_check = isset($argv[5]) && ($argv[5] == "no") ? true : false;
    $fee = (double)0.0025;
 
    echo "Market: ". $pair;
    echo "\n";
    echo "Operation: ". $type . "\n";
    echo "Amount: ". $amount . "\n";
    echo "Rate: ". $rate . "\n";
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
$result = $result['result'];

/* check your ratio sell */
if(!$no_check){
    if($type == "SELL" && $rate < $result['Bid']){
        die(sprintf("\nERROR: You can not sell cheaper(%0.8f) than highest bid(%0.8f)\n\n", $rate, $result['Bid']));
    }elseif($type == "BUY" && $rate > $result['Ask']){
        die(sprintf("\nERROR: You can not buy more expensive(%0.8f) than lowest ask(%0.8f)\n\n", $rate, $result['Ask']));
    }
}

/* get your balances */
$coins = explode("-", $pair);
$result = $api->get_balances();
if(isset($result['error'])){
    print_r($result);
    exit(-1);
}

if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

echo "Your balances\n";

foreach($result['result'] as $key => $value) {
    $balance = (double)$value['Balance'];
    $coin = $value['Currency'];
    if($coin == $coins[0]){
        $btc_amount = $value['Available'];
    }elseif($coin == $coins[1]){
        $coin_amount = $value['Available'];
    }
    if ($balance > 0.0) {
        echo $coin . ":\n";
        echo "\tbalance: " . $value['Balance'] . "\n";
        echo "\tavailable: " . $value['Available'] . "\n";
        echo "\tpending: " . $value['Pending'] . "\n";
    }
}

/* place order */
if($type == "BUY"){
    /* get total balance */
    if($amount == "all") {
        $amount = bcdiv($btc_amount, $rate, 8);
        $amount -= bcmul($amount, $fee, 8);
        echo "buying $amount $coins[1]\n";
    }
    $result = $api->buy_limit($pair, $amount, $rate);

}else{
    /* get total balance */
    if($amount == "all") {
        $amount = round((double)$coin_amount, 8, PHP_ROUND_HALF_DOWN);
        echo "selling $amount $coins[1]\n";
    }
    $result = $api->sell_limit($pair, $amount, $rate);

}

if(!$result['success']){
    print_r("\n".$result['message']."\n\n");
    exit(-1);
}
if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

print_r($result);

$result = $result['result'];
//echo "\nYou place a $type order ".$result['orderNumber']." with amount ". $amount." ".$coins[1]." at $rate BTC/".$coins[1]."\n";
