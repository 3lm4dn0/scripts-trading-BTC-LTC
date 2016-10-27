<?php

include 'APIPoloniex.php';

function print_r2($val){
	print_r($val);
}

$key = getenv('BTT_KEY');
$apisecret = getenv('BTT_PASS');

$api = new APIPoloniex($key, $apisecret);

/**
 * Public API
 */

$pair = "BTC_ETH";
$TABLE= "chart_data_eth";
$FROM_DATE = new DateTime("2016-03-17");
$FILE = "$TABLE_poloniex_".$FROM_DATE->format('Y-m-d').".json";

if(!file_exists($FILE)){
    echo "Downloading data...";
    // candlestick period in seconds; valid values are 300, 900, 1800, 7200, 14400, and 86400
    $period = 300;
    $date = $FROM_DATE;
    $start = $date->getTimestamp();
    $end = time();
    $result = $api->get_chart_data($pair, $period, $start, $end);

    $fp = fopen($FILE, 'w');
    fwrite($fp, json_encode($result));
    fclose($fp);
}else{
    $str = file_get_contents($FILE);
    $result = json_decode($str, true);
}

echo count($result);
echo "\n";

//$result = $api->get_trade_history($pair);

$max = (double) 0.0;
$min = (double) 999999.0;
foreach($result as $k => $value){
    $high = (double) $value['high'];
    $low = (double) $value['low'];
    if($high >= $max){
        $max = $high;
        $max_value = $value;
    }

    if($low <= $min){
        $min = $low;
        $min_value = $value;
    }
}

echo "Min value: \n";
print_r($min_value);
echo "Max value: \n";
print_r($max_value);
echo "Diff: " . ((double)$max_value['high'] - (double)$min_value['low'])."\n";

// Create SQL:
$values_array = array();
foreach($result as $k => $value){
    $values_array[] = "('" . date('Y-m-d H:i:s', $value['date']) . "', " . (double)$value['high'] .", ". (double)$value['low'] .", ". (double)$value['open'] .", ". (double)$value['close'] .", ". (double)$value['volume'] .", ". (double)$value['quoteVolume'] .", ". (double)$value['weightedAverage'] . ")";
}

// Insert in Database
echo "connecting... ";
$conn =  mysql_connect('localhost', 'poloniex_user', 'tWJvnuBDuPXx4X2z_mysql');
if (!$conn) {
    die('No pudo conectarse: ' . mysql_error());
}
echo 'Conectado satisfactoriamente'."\n";

// remove data to update new values
/*
$retval = mysql_query('TRUNCATE TABLE poloniex.$TABLE;');
if(! $retval ) {
    die('Could not truncate table: ' . mysql_error());
}
*/
$start_date = $FROM_DATE->format('Y-m-d')." 00:00:01";
$end_date = $FROM_DATE->format('Y-m-d')." 23:59:59";
$sql = "DELETE FROM poloniex.$TABLE WHERE DATE(date) BETWEEN DATE('".$start_date."') AND DATE('".$end_date."');";
$retval = mysql_query($sql);   

$sql = 'INSERT INTO '.$TABLE.' '.
      '(date, high, low, open, close, volume, quoteVolume, weightedAverage) '.
      'VALUES '. join(",", $values_array);

   mysql_select_db('poloniex');
   $retval = mysql_query( $sql, $conn );
   
   if(! $retval ) {
      die('Could not enter data: ' . mysql_error());
   }
   
   echo "Entered data successfully\n";



mysql_close($conn);

echo "Now you can get min and max with SQL like:";
echo "\n";
echo "sql> SELECT date, MIN(low) as minlow, MAX(high) as maxhigh, MAX(high)-MIN(low) as dif, SUM(volume) as volume FROM $TABLE GROUP BY MONTH(date), DAY(date), HOUR(date) ORDER BY $TABLE.date DESC;\n";
