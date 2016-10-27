<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}


if(count($argv) != 3){
    echo "usage ".$argv[0]." MARKET FROM_DATE\n";
    echo "\texample: ".$argv[0]." BTC_ETH 2016-03-17\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $FROM_DATE = new DateTime($argv[2]);
    $acc_min = (double)0.025;
    $acc_max = (double)0.05;
    $DATABASE = "poloniex";
    $TABLE = "chart_data";
    echo "Market: ". $pair;
    echo "\n";
    echo "From: " . $FROM_DATE->format('Y-m-d H:i:s');
    echo "\n";
    echo "Database: ". $DATABASE;
    echo "\n";
    echo "Table: ". $TABLE;
    echo "\n";
}

$api = new APIPoloniex("", "");

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

/*
 * Public API
 */

echo "Downloading data...";
// candlestick period in seconds; valid values are 300, 900, 1800, 7200, 14400, and 86400
$period = 300;
$start = $FROM_DATE->getTimestamp();
$end = time();
$result = $api->get_chart_data($pair, $period, $start, $end);
echo "Total records: " . count($result) . "\n";

/* Initial values */
$previous_previous_low = $previous_low = $previous_ep = $ep = $result[0]['low'];
$previous_previous_high =$previous_high = $psar = $result[0]['high'];
$acc = $acc_min;
$previous_uptrend = $uptrend = false;
$initial_psar = 0.0;
$psar_ep_acc = bcmul(bcsub($psar, $ep, 8), $acc, 8);

printf("date\t\t high\t\t low\t\t EP\t\t Acc\t\t INIT PSAR\t PSAR\t\tup/down\n");
printf("================================================================================================================================================\n");
foreach($result as $value){
    $date = $value['date'];
    $high = $value['high'];
    $low = $value['low'];
    $close = $value['close'];

    printf("%s  %0.8f\t%0.8f\t%0.8f\t%0.8f\t%0.8f\t%0.8f\t%s\n",
        date('Y-m-d H:i', $value['date']), $high, $low,
        $ep, $acc, $initial_psar, $psar, $uptrend ? "UP" : "DOWN");
    /*
    printf("%s: %s\n",
        date('Y-m-d H:i', $value['date']), $uptrend ? "UP" : "DOWN");
    */

    /* calculate Parabolic SAR */
    /***
     * original from https://www.tradinformed.com/calculate-psar-indicator-revised/
     * Video formula: https://www.youtube.com/watch?v=MuEpGBAH7pw
    C = value['high']
    D = value['low']
    G = $ep
    H = Acc
    I = (psar-ep)*acc
    J = initial psar
    K = psar
    L = trend

    Second Line:
    G6 =IF(L6=”falling”,MIN(G5,D6),IF(L6=”rising”,MAX(G5,C6),””))
    H6 =IF(AND(L6=L5,G6<>G5,H5<$H$3),H5+$H$2,IF(AND(L5=L6,G6=G5),H5,IF(L5<>L6,$H$2,$H$3)))
    I6 =(K6-G6)*H6
    J6 =IF(L5=”falling”,MAX(K5-I5,C5,C4),IF(L5=”rising”,MIN(K5-I5,D5,D4),””))
    K6 =IF(AND(L5=”falling”,C6<J6),J6,IF(AND(L5=”rising”,D6>J6),J6,IF(AND(L5=”falling”,C6>=J6),G5,IF(AND(L5=”rising”,D6<=J6),G5,””))))
    L6 =IF(K6>E6,”Falling”,”Rising”)
     */
    $psar_minus_psar_ep_acc = bcsub($psar, $psar_ep_acc, 8);

    $initial_psar = $uptrend
        ? min($psar_minus_psar_ep_acc, $previous_low, $previous_previous_low)
        : max($psar_minus_psar_ep_acc, $previous_high, $previous_previous_high);

    $psar = ((!$uptrend && $high<$initial_psar)
            || ($uptrend && $low>$initial_psar))
        ? $initial_psar
        : ((!$uptrend && $high>=$initial_psar)
            || ($uptrend && $low<=$initial_psar))
            ? $ep : die("PSAR null.\n");

    $uptrend = ($psar <= $close);

    $ep = $uptrend ? max($ep, $high) : min($ep, $low);

    $acc = ($previous_uptrend == $uptrend && $previous_ep != $ep && $acc < $acc_max)
        ? bcadd($acc, $acc_min, 8)
        : ($previous_uptrend == $uptrend && $previous_ep == $ep)
            ? $acc
            : ($previous_uptrend != $uptrend) ? $acc_min : $acc_max;

    $psar_ep_acc = bcmul(bcsub($psar, $ep, 8), $acc, 8);

    /* re-initialization */
    $previous_ep = $ep;
    $previous_uptrend = $uptrend;
    $previous_previous_high = $previous_high;
    $previous_previous_low = $previous_low;
    $previous_high = $high;
    $previous_low = $low;
}