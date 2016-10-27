<?php

include 'APIPoloniex.php';
include 'APICryptowatch.php';

/**
 * @param $api
 * @param $pair
 * @param $order
 * @return bool
 */
function finished_or_cancel_open_order($api, $pair, $order){
    if(isset($order['error'])){
        printf("ERROR: %s.\n", $order['error']);
        return false;
    }

    if(!isset($order['orderNumber'])){
        printf("ERROR: order not placed.\n");
        return false;
    }

    $result = $api->get_open_orders($pair);

    foreach($result as $open_order){
        if($open_order['orderNumber'] == $order['orderNumber']){
            if($open_order['startingAmount'] > $open_order['amount']){
                printf("Order number '%d' partially completed with amount '%0.8f'\n", $order['orderNumber'], bc_sub($open_order['startingAmount'], $open_order['amount'], 8));
                printf("Waiting 20 seconds to complete...\n");
                sleep(20);
                printf("Canceling order number '%d'...\n", $order['orderNumber']);
                $api->cancel_order($pair, $order['orderNumber']);
                return true;
                // TODO return a third state "partially_completed"
            }else {
                printf("Canceling order number '%d'...\n", $order['orderNumber']);
                $api->cancel_order($pair, $order['orderNumber']);
                return false;
            }
        }
    }

    return true;
}

/**
 * Get rate from the first orders in the market place
 * @param $api
 * @param $pair
 * @param $buy
 * @return mixed
 */
function get_rate_from_market_orders($api, $pair, $buy){
    $market = $api->get_ticker($pair);
    return $buy ? $market['highestBid'] : $market['lowestAsk'];
}

/**
 * @param $api
 * @param $pair
 * @param $buy
 * @param $data_chart
 * @return mixed
 */
function get_rate($api, $pair, $buy, $data_chart){
    $rate = $buy ? $data_chart['low'] : $data_chart['high'];
    $market = $api->get_ticker($pair);
    if($buy && $rate > $market['highestBid']){
        printf("WARNING: You can not buy more expensive(%0.8f) than highest bid(%0.8f)\n", $rate, $market['highestBid']);
        $rate = $market['highestBid'];
    }elseif(!$buy && $rate < $market['lowestAsk']){
        printf("WARNING: You can not sell cheaper(%0.8f) than lower ask(%0.8f)\n", $rate, $market['lowestAsk']);
        $rate = $market['lowestAsk'];
    }

    return $rate;
}

/**
 * calculate total amount from cryptocurrency to place a order
 * @param $api
 * @param $pair
 * @param $buy
 * @param $amount
 * @param $rate
 * @return array|string
 */
function calculate_amount($api, $pair, $buy, $amount, $rate){

    $balance = $api->get_complete_balances();
    $coins = explode("_", $pair);

    if($buy){
        if($amount == "all") {
            $amount = bcsub(bcdiv((double)$balance['BTC']['available'], (double)$rate, 8), 0.00000001, 8);
        }elseif(bcsub(bcmul($amount, $rate, 8), 0.00000001, 8) < $balance['BTC']['available']){
            return array("error" => "Insufficient balance");
        }
    }else{
        if($amount == "all") {
            $amount = $balance[$coins[1]]['available'];
        }elseif($amount < $balance[$coins[1]]['available']){
            return array("error" => "Insufficient balance");
        }
    }

    return $amount;
}

/**
 * Place an order to buy or sell $amount with a given $rate
 * @param $api
 * @param $pair
 * @param $buy
 * @param $amountToChange
 * @param $rate
 * @return array the given order
 */
function place_order($api, $pair, $buy, $amountToChange, $rate){
    $amount = calculate_amount($api, $pair, $buy, $amountToChange, $rate);

    if($amount < 0.001){
        printf("Insufficient available balance\n");
        return array("error" => "Insufficient available balance");
    }

    if($buy){
        $result = $api->buy($pair, $rate, $amount);
    }else{
        $result = $api->sell($pair, $rate, $amount);
    }

    if(isset($result['error']) || !isset($result['orderNumber'])){
        printf("ERROR: %s\n", $result['error']);
        return $result;
    }

    printf("INFO: placed order to '%s' from amount %0.8f at rate %0.8f\n", ($buy ? "BUY" : "SELL"), $amount, $rate);
    return $result;
}

/**
 * Retrive data chart market prices from Poloniex API
 * @param $api
 * @param $pair
 * @param $period
 * @return mixed
 */
function get_data_chart_from_poloniex($api, $pair, $period) {
    if($period == 60 && $pair != "BTC_ETH"){
        die(sprinf("%d period not supported for the market %s\n", $period, $pair));
    }

    // candlestick period in seconds; valid values are 300, 900, 1800, 7200, 14400, and 86400
    $start = strtotime('-12 hours', time());
    $end = time();
    $result = $api->get_chart_data($pair, $period, $start, $end);

    return $result;
}

function print_r2($val){
    print_r($val);
}

if(count($argv) != 5){
    echo "usage ".$argv[0]." MARKET amount diff_to_buy diff_to_sell\n";
    echo "\texample: ".$argv[0]." BTC_ETH 1.5 0.0005 0.001\n";
    echo "\texample: ".$argv[0]." BTC_XMR all 0.0007 0.0003\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $amountToChange = $argv[2];
    $diff_to_buy = $argv[3];
    $diff_to_sell = $argv[4];
    $coins = explode("_", $pair);
    $period = 300;
    $threshold = (double)0.0001;
    printf("Market: %s\nAmount: %s %s\nDifference to buy: %0.8f\nDifference to sell: %0.8f\n", $pair, $amountToChange, $coins[1], $diff_to_buy, $diff_to_sell);
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
$order = array();
$result = $api->get_open_orders($pair);
if(isset($result['error'])){
    printf("ERROR: %s\n", $result['error']);
    exit(-1);
}
if(empty($result)){
    printf("ERROR: no open orders.\n");
    exit(-1);
}
$order = $result[0];
$last_rate = $result[0]['rate'];
$buy = ($result[0]['type']=="buy");

printf("Actual open open order to '%s' at rate %0.8f\n", $buy ? "BUY" : "SELL", $last_rate);
printf("It will try to buy at rate of %0.8f\n", bcsub($last_rate, $diff_to_buy, 8));
printf("It will try to sell at rate of %0.8f\n", bcadd($last_rate, $diff_to_sell, 8));

for(;;){
    $result = $api->get_open_orders($pair);

    if(isset($result['error'])){
        printf("ERROR: %s\n", $result['error']);
    }elseif(empty($result)){

        printf("INFO: Congratulations! order '%s' with number '%d' was successful completed at rate %0.8f\n", ($buy ? "BUY" : "SELL"), $order['orderNumber'], $last_rate);

        $rate = $buy ? bcadd($diff_to_sell, $last_rate, 8) : bcsub($last_rate, $diff_to_buy, 8);
        //sleep(61);
        //$market_price = get_rate_from_market_orders($api, $pair, $buy);
        //$rate = $buy ? bcadd($diff_to_sell, $market_price, 8) : bcsub($market_price, $diff_to_buy, 8);
        $diff = $buy ? bcsub($rate, $last_rate, 8) : bcsub($last_rate, $rate, 8);
        if ($diff >= $threshold) {
            $result = place_order($api, $pair, !$buy, $amountToChange, $rate);
            if(!isset($result['error']) || isset($result['orderNumber'])){
                // re-initialize last rate
                $last_rate = $rate;
                $order = $result;
                $buy = (!$buy);
            }
        }else{
            printf("ERROR: insufficient difference between last rate %0.8f and new rate %0.8f (difference %0.8f, threshold %0.8f)\n", $last_rate, $rate, $diff, $threshold);
        }
    }

    sleep(5);
}
