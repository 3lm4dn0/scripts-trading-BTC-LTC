<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 4){
    echo "usage ".$argv[0]." MARKET [BUY | SELL] [ AMOUNT | \"all\" ]\n";
    echo "\texample: ".$argv[0]." BTC_ETH BUY 20.55\n";
    echo "\texample: ".$argv[0]." BTC_ETH SELL all\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $type = $argv[2];
    $amount = $argv[3];
    $DATABASE = "poloniex";
    $TABLE = "user_open_orders";
 
    echo "Market: ". $pair;
    echo "\n";
    echo "Operation: ". $type . "\n";
    echo "Amount: ". $amount . "\n";
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

/* check your ratio sell */
if($type == "SELL"){
    $rate = $result['lowestAsk'];
}elseif($type == "BUY"){
    $rate = $result['highestBid'];
}
    echo "ASK: ".$result['lowestAsk']."\n";
    echo "BID: ".$result['highestBid']."\n";
echo "Rate: ". $rate . "\n";

/* get your balances */
$result = $api->get_complete_balances();
if(isset($result['error'])){
    print_r($result);
    exit(-1);
}
if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

/* show your balances */
echo "Your balances\n";
$coins = explode("_", $pair);
foreach($result as $coin => $value) {
    if (in_array($coin, $coins)) {
        echo $coin . ":\n";
        echo "\tavailable: " . $value['available'] . "\n";
        echo "\tonOrders: " . $value['onOrders'] . "\n";
        echo "\tbtcValue: " . $value['btcValue'] . "\n";
    }
}

/* place order */
if($type == "BUY"){
    /* get total balance */
    if($amount == "all") {
        $amount = round((double)$result['BTC']['available'] / (double)$rate, 8, PHP_ROUND_HALF_UP);
    }
    $result = $api->buy($pair, $rate, $amount);
}else{
    /* get total balance */
    if($amount == "all") {
        $amount = round((double)$result[$coins[1]]['available'], 8, PHP_ROUND_HALF_UP);
    }
    $result = $api->sell($pair, $rate, $amount);
}

if(isset($result['error'])){
    print_r($result);
    exit(-1);
}
if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}
print_r($result);

echo "\nYou place a $type order ".$result['orderNumber']." with amount ". $amount." ".$coins[1]." at $rate BTC/".$coins[1]."\n";
