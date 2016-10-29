<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 5){
    echo "usage ".$argv[0]." MARKET [BUY | SELL] [ AMOUNT | \"all\" ] RATE\n";
    echo "\texample: ".$argv[0]." BTC_ETH BUY 20.55 0.024\n";
    echo "\texample: ".$argv[0]." BTC_ETH SELL all 0.033\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $type = $argv[2];
    $amount = $argv[3];
    $rate = $argv[4];
    $DATABASE = "poloniex";
    $TABLE = "user_open_orders";
 
    echo "Market: ". $pair;
    echo "\n";
    echo "Operation: ". $type . "\n";
    echo "Amount: ". $amount . "\n";
    echo "Rate: ". $rate . "\n";
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
if($type == "SELL" && $rate < $result['highestBid']){
    die(sprintf("\nERROR: You can not sell cheaper(%0.8f) than highest bid(%0.8f)\n\n", $rate, $result['highestBid']));
}elseif($type == "BUY" && $rate > $result['lowestAsk']){
    die(sprintf("\nERROR: You can not buy more expensive(%0.8f) than lowest ask(%0.8f)\n\n", $rate, $result['lowestAsk']));
}

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
        $amount = bcdiv((double)$result['BTC']['available'], (double)$rate, 8);
    }
    $result = $api->buy($pair, $rate, $amount);

}else{
    /* get total balance */
    if($amount == "all") {
        $amount = round((double)$result[$coins[1]]['available'], 8, PHP_ROUND_HALF_DOWN);
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
