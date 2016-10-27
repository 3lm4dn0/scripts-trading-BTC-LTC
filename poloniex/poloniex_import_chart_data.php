<?php

include 'APIPoloniex.php';
include 'APICryptowatch.php';

function print_r2($val){
    print_r($val);
}


if(count($argv) != 4){
    echo "usage ".$argv[0]." MARKET period FROM_DATE\n";
    echo "\texample: ".$argv[0]." BTC_ETH 60 2016-03-17\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $period = $argv[2];
    $FROM_DATE = new DateTime($argv[3]);
    $DATABASE = "poloniex";
    $TABLE = "chart_data";
    $TABLE_MARKET = "trade_history";
    echo "Market: ". $pair;
    echo "\n";
    echo "Period: " . $period; // candlestick period in seconds; valid values are 60(Cryptowatch API), 300, 900, 1800, 7200, 14400, and 86400
    echo "\n";
    echo "From: " . $FROM_DATE->format('Y-m-d H:i:s');
    echo "\n";
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

/**
 * Public API
 */
$api = new APIPoloniex("", "");
$api_cryptowatch = new APICryptowatch();

echo "Downloading data...";
$start = $FROM_DATE->getTimestamp();
$end = time();
/* calc last trend */
if($period == 60){
    $result = $api_cryptowatch->get_data_chart_poloniex_ethbtc($pair, $period);
}else{
    $result = $api->get_chart_data($pair, $period, $start, $end);
}
echo "Total records: " . count($result) . "\n";

// Create SQL:
$values_array = array();
foreach($result as $k => $value){
    $values_array[] = "('".$pair."', $period, '" . date('Y-m-d H:i:s', $value['date']) . "', " . (double)$value['high'] .", ". (double)$value['low'] .", ". (double)$value['open'] .", ". (double)$value['close'] .", ". (double)$value['volume'] .", ". (double)$value['quoteVolume'] .", ". (double)$value['weightedAverage'] . ")";
}

// delete existing data from the given date
$start_date = $FROM_DATE->format('Y-m-d')." 00:00:00";
$end_date = date("Y-m-d H:i:s");
$sql = "DELETE FROM $DATABASE.$TABLE WHERE market='".$pair."' AND period=$period AND DATE(date) BETWEEN DATE('".$start_date."') AND DATE('".$end_date."');";
$retval = mysql_query($sql);
if(! $retval ) {
    die('Could not delete data: ' . mysql_error());
}

// Insert new data
$sql = 'INSERT IGNORE INTO '.$TABLE.' '.
      '(market, period, date, high, low, open, close, volume, quoteVolume, weightedAverage) '.
      'VALUES '. join(",", $values_array);

   mysql_select_db($DATABASE);
   $retval = mysql_query( $sql, $conn );
   
   if(! $retval ) {
      die('Could not enter data: ' . mysql_error());
   }
   
   echo "Entered data successfully\n";

/**
*   Show last 10 records group by hour
*/
echo "\n";
echo "Now you can get min and max with SQL like:\n";
$sql = "SELECT date, MIN(low) as minlow, MAX(high) as maxhigh, MAX(high)-MIN(low) as dif, SUM(volume) as volume FROM $DATABASE.$TABLE WHERE market='".$pair."' AND period=$period GROUP BY MONTH(date), DAY(date), HOUR(date) ORDER BY $DATABASE.$TABLE.date DESC LIMIT 20;";
echo $sql;
echo "\n\n";
$result = mysql_query( $sql, $conn );

if(! $result ) {
  die('Could get query: ' . mysql_error());
}

printf("date\t\t\t|\tmin low\t\t|\tmax high\t|\tdif\t\t|\tvolume\n");
printf("=====================================================================================================================\n");
while ($fila = mysql_fetch_array($result, MYSQL_NUM)) {
    printf("%s\t|\t%.8f\t|\t%.8f\t|\t%.8f\t|\t%.8f\n", $fila[0], $fila[1], $fila[2], $fila[3], $fila[4]);  
}

/**
*   Show last 5 records
*/
echo "\n";
echo "Now you can get min and max with SQL like:\n";
$sql = "SELECT date, low, high, high-low as dif, volume FROM $DATABASE.$TABLE WHERE market='".$pair."' AND period=$period ORDER BY $DATABASE.$TABLE.date DESC LIMIT 5;";
echo $sql;
echo "\n\n";
$result = mysql_query( $sql, $conn );

if(! $result ) {
  die('Could get query: ' . mysql_error());
}

printf("date\t\t\t|\tmin low\t\t|\tmax high\t|\tdif\t\t|\tvolume\n");
printf("=====================================================================================================================\n");
while ($fila = mysql_fetch_array($result, MYSQL_NUM)) {
    printf("%s\t|\t%.8f\t|\t%.8f\t|\t%.8f\t|\t%.8f\n", $fila[0], $fila[1], $fila[2], $fila[3], $fila[4]);  
}


mysql_close($conn);
