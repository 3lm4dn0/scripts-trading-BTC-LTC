<?php

include 'APIPoloniex.php';
include 'APICryptowatch.php';

function import_order_history($api, $pair, $conn){
    $DATABASE = "poloniex";
    $TABLE = "user_trade_history";
    $start = strtotime('-5 minutes', time());
    $end = time();
    $result = $api->get_my_trade_history($pair, $start, $end);

    $values_array = array();
    foreach ($result as $k => $value) {
        $values_array[] = "('" . $pair . "', " . $value['globalTradeID'] . ", " . $value['tradeID'] . ", '" . $value['date'] . "', " . (double)$value['rate'] . ", " . (double)$value['amount'] . ", " . (double)$value['total'] . ", " . (double)$value['fee'] . ", " . $value['orderNumber'] . ", '" . $value['type'] . "', '" . $value['category'] . "')";
    }

    /* insert ignoring duplicated entries */
    $sql = 'INSERT IGNORE INTO ' . $TABLE . ' ' .
        '(`market`, `globalTradeID`, `tradeID`, `date`, `rate`, `amount`, `total`, `fee`, `orderNumber`, `type`, `category`) ' .
        'VALUES ' . join(",", $values_array);

    mysql_select_db($DATABASE);
    $retval = mysql_query($sql, $conn);
    if (!$retval) {
        echo mysql_error() . "\n";
    }
}

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
                printf("Order number '%d' partially completed with amount '%0.8f'\n", $order['orderNumber'], bcsub($open_order['startingAmount'], $open_order['amount'], 8));
                printf("Waiting 30 seconds to complete...\n");
                sleep(30);
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
 * @param $uptrend
 * @return mixed
 */
function get_rate_from_market_orders($api, $pair, $uptrend){
    $market = $api->get_ticker($pair);
//    return $uptrend ? $market['highestBid'] : $market['lowestAsk'];
    return $market['last'];
}

/**
 * @param $api
 * @param $pair
 * @param $uptrend
 * @param $data_chart
 * @return mixed
 */
function get_rate($api, $pair, $uptrend, $data_chart){
    $rate = $uptrend ? $data_chart['low'] : $data_chart['high'];
    $market = $api->get_ticker($pair);
    if($uptrend && $rate > $market['highestBid']){
        printf("WARNING: You can not buy more expensive(%0.8f) than highest bid(%0.8f)\n", $rate, $market['highestBid']);
        $rate = $market['highestBid'];
    }elseif(!$uptrend && $rate < $market['lowestAsk']){
        printf("WARNING: You can not sell cheaper(%0.8f) than lower ask(%0.8f)\n", $rate, $market['lowestAsk']);
        $rate = $market['lowestAsk'];
    }

    return $rate;
}

/**
 * calculate total amount from cryptocurrency to place a order
 * @param $api
 * @param $pair
 * @param $uptrend
 * @param $amount
 * @param $rate
 * @return array|string
 */
function calculate_amount($api, $pair, $uptrend, $amount, $rate){

    $balance = $api->get_complete_balances();
    $coins = explode("_", $pair);

    if($uptrend){
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
 * @param $uptrend
 * @param $amountToChange
 * @param $rate
 * @return array the given order
 */
function place_order($api, $pair, $uptrend, $amountToChange, $rate){
    $amount = calculate_amount($api, $pair, $uptrend, $amountToChange, $rate);

    if($amount < 0.001){
        printf("Insufficient amount\n");
        return array("error" => "Insufficient amount");
    }

    if($uptrend){
        $result = $api->buy($pair, $rate, $amount);
    }else{
        $result = $api->sell($pair, $rate, $amount);
    }

    if(isset($result['error']) || !isset($result['orderNumber'])){
        printf("ERROR: %s\n", $result['error']);
        return $result;
    }

    printf("INFO: placed order to '%s' from amount %0.8f at rate %0.8f\n", ($uptrend ? "BUY" : "SELL"), $amount, $rate);
    return $result;
}

/**
 * @param $api
 * @param $uptrend
 * @param $order
 * @param $rate
 * @return array
 */
function move_order($api, $uptrend, $order, $rate){
    printf("Trying to move order '%d' to rate %0.8f...\n", $order['orderNumber'], $rate);
    if($uptrend){
        $amount = bcsub(bcdiv((double)$order['total'], (double)$rate, 8), 0.00000001, 8);
    }else{
        $amount = $order['amount'];
    }

    if($amount < 0.001){
        return array("error" => "Insufficient amount");
    }

    return $api->move_order($order['orderNumber'], $rate, $amount);
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

/**
 * Calculate Parabolic SAR
 * based in http://stockcharts.com/school/doku.php?id=chart_school:technical_indicators:parabolic_sar
 * @param $api
 * @param $pair
 * @return array
 */
function calculate_parabolic_sar($data_chart, $af_min, $af_max){
    /* Initial values */
    $previous_previous_low = $previous_low = $data_chart[0]['low'];
    $previous_previous_high =$previous_high = $data_chart[0]['high'];
    $previous_psar = $psar = $data_chart[0]['high'];
    $previous_ep = $ep = $data_chart[0]['low'];
    $previous_af = $af = $af_min;
    $previous_uptrend = $uptrend = false;
    array_shift($data_chart);

    $values_array = array();
    foreach($data_chart as $value){
        $date = $value['date'];
        $high = $value['high'];
        $low = $value['low'];
        $open = $value['open'];
        $close = $value['close'];

        if($previous_uptrend) {
            $ep = $high;
            //$af = ($ep > $previous_ep && $af < $af_max) ? bcadd($af, $af_min, 8) : $af;
            $af = ($ep > $previous_ep && $af < $af_max) ? bcadd($af, $af_min, 8) : ($ep == $previous_ep && $uptrend == $previous_uptrend) ? $af : ($uptrend != $previous_uptrend) ? $af_min : $af_max;
            $psar = min($previous_low, $previous_previous_low, bcadd($previous_psar, bcmul($previous_af, bcsub($previous_ep, $previous_psar, 8), 8), 8));
        }else{
            $ep = $low;
            //$af = ($ep < $previous_ep && $af < $af_max) ? bcadd($af, $af_min, 8) : $af;
            $af = ($ep < $previous_ep && $af < $af_max) ? bcadd($af, $af_min, 8) : ($ep == $previous_ep && $uptrend == $previous_uptrend) ? $af : ($uptrend != $previous_uptrend) ? $af_min : $af_max;
            $psar = max($previous_high, $previous_previous_high, bcsub($previous_psar, bcmul($previous_af, bcsub($previous_psar, $previous_ep, 8), 8), 8));
        }
        $uptrend = ($psar < $close);
        $values_array[] = array('date' => date("Y-m-d H:i:s", $date), 'low' => $low, 'high' => $high, 'open' => $open, 'close' => $close, 'uptrend' => $uptrend);

        /* re-initialization */
        $previous_af = $af;
        $previous_psar = $psar;
        $previous_ep = $ep;
        $previous_uptrend = $uptrend;
        $previous_previous_high = $previous_high;
        $previous_previous_low = $previous_low;
        $previous_high = $high;
        $previous_low = $low;
    }

    return $values_array;
}

function print_r2($val){
    print_r($val);
}

if(count($argv) != 8){
    echo "usage ".$argv[0]." MARKET amount period_in_seconds af_min af_max threshold_buy threshold_sell\n";
    echo "\texample: ".$argv[0]." BTC_ETH 1.5 60 0.02 0.2 0.001 0.002\n";
    echo "\texample: ".$argv[0]." BTC_XMR all 300 0.025 0.25 0.0003 0.00004\n";
    echo "\toptions:\n";
    echo "\t\tmarket: pairs of cryptocurrency underscore separated, like BTC_ETH.\n";
    echo "\t\tamount: amount: valid values float numbers | all to use all balance.\n";
    echo "\t\tperiod_in_seconds: valid values are 60(Cryptowat.ch), 300, 900, 1800, 7200, 14400, and 86400\n";
    echo "\t\taf_min: minimum accumulator factor to calculate Parabolic SAR. Recomended 0.02 or 0.025.\n";
    echo "\t\taf_max: maximum accumulator factor to calculate Parabolic SAR. Recomended 0.05 or 0.01.\n";
    echo "\t\tthreshold_buy: minimum amount difference to buy.\n";
    echo "\t\tthreshold_sell: minimum amount difference to sell.\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $amountToChange = $argv[2];
    $period = $argv[3];
    $af_min = (double)$argv[4];
    $af_max = (double)$argv[5];
    $threshold_to_buy = (double)$argv[6];
    $threshold_to_sell = (double)$argv[7];
    $tries = 3;
    $waitOrderTime = bcdiv($period, $tries, 0);
    $coins = explode("_", $pair);
    printf("Market: %s\nAmount: %s ".$coins[1]."\nPeriod %d" .
    "\nAF min: %0.3f\nAF max: %0.3f\nThreshold buy: %0.8f\nThreshold sell: %0.8f\n" .
    "Wait time order: %d sec\nTries: %d\n", $pair, $amountToChange, $period,
        $af_min, $af_max, $threshold_to_buy, $threshold_to_sell,
        $waitOrderTime, $tries);
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

$DATABASE_HOST = getenv('DATABASE_HOST');
$DATABASE_USER = getenv('DATABASE_USER');
$DATABASE_PASS = getenv('DATABASE_PASS');
if(empty($DATABASE_HOST)){
    echo "Set environment variables from database:\n";
    echo "export DATABASE_HOST=\"localhost\"\n";
    echo "export DATABASE_USER=\"your user\"\n";
    echo "export DATABASE_PASS=\"your password\"\n";
    exit(-1);
}

echo "connecting... ";
$conn =  mysql_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS);
if (!$conn) {
    die('No pudo conectarse: ' . mysql_error());
}
echo 'Conectado satisfactoriamente'."\n";

/**
 * PRIVATE API
 */
$api = new APIPoloniex($key, $apisecret);
$api_cryptowatch = new APICryptowatch();

/* get last trade history */
$start = strtotime('-4 days', time());
$end = time();
$result = $api->get_my_trade_history($pair, $start, $end);
if(isset($result['error'])){
    print_r($result);
    exit(-1);
}
if(empty($result)) {
    echo "Not any trade history since last 4 days. Please place an order.\n";
    exit(-1);
}

$rate = $last_rate = $result[0]['rate'];
$type = $result[0]['type'];
$last_uptrend = ($type == "buy");

$min_changes = 2;
$change_count = 0;
$order = array();
printf("Start from trade history %s to '%s' at '%0.8f' %s\n", $result[0]['date'], strtoupper($type), $last_rate, strtoupper($pair));
for(;;){
    /* calc last trend */
    if($period == 60){
        $data_chart = $api_cryptowatch->get_data_chart_poloniex_ethbtc($pair, $period);
    }else{
        $data_chart = get_data_chart_from_poloniex($api, $pair, $period);
    }
    if(isset($result['error'])){
        print_r($result);
        exit(-1);
    }

    $last_data_chart = end(calculate_parabolic_sar($data_chart, $af_min, $af_max));
    $uptrend = $last_data_chart['uptrend'];
    printf("date: %s; trend: %s; low: %.8f; high: %.8f; close: %.8f\n", $last_data_chart['date'], $uptrend ? "UP" : "DOWN", $last_data_chart['low'], $last_data_chart['high'], $last_data_chart['close']);

    if($uptrend == $uptrend){
        $change_count++;
    }else{
        $change_count=0;
    }

    if($uptrend != $last_uptrend){
        /* get high o low rate from last candle bar */
        $rate = get_rate($api, $pair, $uptrend, $last_data_chart);
        $threshold = $uptrend ? $threshold_to_buy : $threshold_to_sell;

        printf("Change trend from '%s' to '%s'. Trying to '%s' with a threshold of '%0.8f'\n",
            ($last_uptrend?'UP':'DOWN'), ($uptrend?'UP':'DOWN'), ($uptrend?'BUY':'SELL'), $threshold);

        $diff = $uptrend ? bcsub($last_rate, $rate, 8) : bcsub($rate, $last_rate, 8);
        if ($diff >= $threshold) {
            $attemps = 0;
            $max_attemps = $tries;
            $completed = false;
            while (!$completed && $attemps < $max_attemps) {
                $diff = $uptrend ? bcsub($last_rate, $rate, 8) : bcsub($rate, $last_rate, 8);
                if ($diff >= $threshold) {
                    $order = place_order($api, $pair, $uptrend, $amountToChange, $rate);
                } else {
                    printf("ERROR: insufficient difference between last rate %0.8f and new rate %0.8f (difference %0.8f, threshold %0.8f)\n", $last_rate, $rate, $diff, $threshold);
                }

                sleep($waitOrderTime);
                if ($diff >= $threshold) {
                    $completed = finished_or_cancel_open_order($api, $pair, $order);
                }
                $rate = get_rate_from_market_orders($api, $pair, $uptrend);
            }

            if ($completed) {
                import_order_history($api, $pair, $conn);
                printf("INFO: Congratulations! order '%s' with number '%d' was successful completed at rate %0.8f\n", ($uptrend ? "BUY" : "SELL"), $order['orderNumber'], $rate);

                // re-initialize last rate and uptrend
                $last_rate = $rate;
                $last_uptrend = $uptrend;
            }
        }else{
            printf("ERROR: insufficient difference between last rate %0.8f and new rate %0.8f (difference %0.8f, threshold %0.8f)\n", $last_rate, $rate, $diff, $threshold);
        }
    }

    /* wait to the next $period interval */
    $time = time();
    $next = $time+$period-($time%$period);
    time_sleep_until($next);
}

mysql_close($conn);
