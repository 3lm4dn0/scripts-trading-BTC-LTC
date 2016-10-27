<?php

include 'APIPoloniex.php';
include 'APICryptowatch.php';

function print_r2($val){
    print_r($val);
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
        $values_array[] = array('date' => date("Y-m-d H:i:s", $date), 'low' => $low, 'high' => $high, 'open' => $open, 'close' => $close,
            'volume' => $value['volume'], 'quoteVolume' => $value['quoteVolume'], 'weightedAverage' => $value['weightedAverage'],
            'ep' => $ep, 'af' => $af, 'psar' => $psar, 'uptrend' => $uptrend);

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


if(count($argv) != 6){
    echo "usage ".$argv[0]." MARKET period_in_seconds af_min af_max FROM_DATE\n";
    echo "\texample: ".$argv[0]." BTC_ETH 60 0.025 0.05 2016-01-01\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $period = $argv[2];
    $af_min = (double)$argv[3]; //0.025;
    $af_max = (double)$argv[4]; //0.05;
    $FROM_DATE = new DateTime($argv[5]);
    $start = $FROM_DATE->getTimestamp();
    $end = time();
    $DATABASE = "poloniex";
    $TABLE = "chart_data_psar";
    echo "Market: ". $pair;
    echo "\n";
    echo "Period: " . $period; // candlestick period in seconds; valid values are 60(Cryptowatch API), 300, 900, 1800, 7200, 14400, and 86400
    echo "\n";
    echo "AF min: ". $af_min. "\n";
    echo "AF max: ". $af_max. "\n";
    echo "From: " . $FROM_DATE->format('Y-m-d H:i:s') . "\n";
    echo "Database: ". $DATABASE;
    echo "\n";
    echo "Table: ". $TABLE;
    echo "\n";
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
echo "Conectado satisfactoriamente\n";

$api = new APIPoloniex("", "");
$api_cryptowatch = new APICryptowatch();

/*
 * Public API
 */

echo "Downloading data chart...";
$start = $FROM_DATE->getTimestamp();
$end = time();
/* calc last trend */
if($period == 60){
    $result = $api_cryptowatch->get_data_chart_poloniex_ethbtc($pair, $period);
}else{
    $result = $api->get_chart_data($pair, $period, $start, $end);
}

$result = calculate_parabolic_sar($result, $af_min, $af_max);
echo "Total records: " . count($result) . "\n";

//printf("date\t\t\t high\t\t low\t\t ep\t\t af\t\t psar\t\tup/down\n");
//printf("================================================================================================================\n");
$values_array = array();
foreach($result as $value){
    $values_array[] = "('".$pair."', $period, '". $value['date'] ."', $af_min, $af_max, ". (double)$value['high'] .", ". (double)$value['low'] .", ". (double)$value['open'] .", ". (double)$value['close'] .", ". (double)$value['volume'] .", ". (double)$value['quoteVolume'] .", ". (double)$value['weightedAverage'] .", ". $value['ep'] .", ". $value['af'] .", ". $value['psar'] .", '". ($value['uptrend'] ? "UP" : "DOWN") . "')";
}

// delete existing data from the given date
$start_date = $FROM_DATE->format('Y-m-d')." 00:00:00";
$end_date = date("Y-m-d H:i:s");
$sql = "DELETE FROM $DATABASE.$TABLE WHERE market='".$pair."' AND period=$period AND af_min=$af_min AND af_max=$af_max AND DATE(date) BETWEEN DATE('".$start_date."') AND DATE('".$end_date."');";
$retval = mysql_query($sql);
if(! $retval ) {
    die('Could not delete data: ' . mysql_error());
}

// Insert new data
$sql = 'INSERT IGNORE INTO '.$TABLE.' '.
    '(`market`, `period`, `date`, `af_min`, `af_max`, `high`, `low`, `open`, `close`, `volume`, `quoteVolume`, `weightedAverage`, `ep`, `af`, `psar`, `trend`) '.
    'VALUES '. join(",", $values_array);
mysql_select_db($DATABASE);
$retval = mysql_query( $sql, $conn );

if(! $retval ) {
    die('Could not enter data: ' . mysql_error());
}

echo "Entered data successfully\n";
mysql_close($conn);