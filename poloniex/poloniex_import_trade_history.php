<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}

/**
*   Show last 10 records
*/
function get_last_orders($conn, $DATABASE, $TABLE, $pair){
    echo "Show last 10 records:\n";
    $sql = "SELECT `date`, `type`, `rate`, `amount`, `total` FROM $DATABASE.$TABLE 
            WHERE market='".$pair."' ORDER BY $DATABASE.$TABLE.date DESC LIMIT 10;";
    echo $sql;
    echo "\n\n";
    $result = mysql_query( $sql, $conn );

    if(! $result ) {
      echo mysql_error()."\n";
    }

    printf("date\t\t\t|\ttype\t\t|\trate\t\t|\tamount\t\t|\ttotal\n");
    printf("====================================================================================================================\n");
    while ($fila = mysql_fetch_array($result, MYSQL_NUM)) {
        printf("%s\t|\t%s\t\t|\t%.8f\t|\t%.8f\t|\t%.8f\n", $fila[0], $fila[1], $fila[2], $fila[3], $fila[4]);  
    }
}

/**
*   Show total order of sell and buy by MONTH and HOUR
*/
function get_orders_by_hour($conn, $DATABASE, $TABLE, $pair){
    echo "\n\n";
    echo "Show total order of sell and buy by HOUR\n";
    $sql = "SELECT MONTHNAME(date) monthname, HOUR(date) hour, 'sell' as type, COUNT(type) as total_orders, sum(total) as volume_btc, AVG(`rate`) as avg_rate, DAY(`date`) as day, `date` FROM `".$TABLE."` WHERE market='".$pair."' AND type='sell' GROUP BY MONTH(date), DAY(date), HOUR(date)
    UNION
    SELECT MONTHNAME(date) monthname, HOUR(date) hour, 'buy' as type , COUNT(type) as total_orders, sum(total) as volume_btc, AVG(`rate`) as avg_rate, DAY(`date`) as day, `date` FROM `".$TABLE."` WHERE market='".$pair."' AND type='buy' GROUP BY MONTH(date), DAY(date), HOUR(date) 
   ORDER BY `date` DESC, `type` LIMIT 4;";
    echo $sql;
    echo "\n\n";
    $result = mysql_query( $sql, $conn );

    if(! $result ) {
      echo mysql_error()."\n";
    }

    printf("month\t\t|\thour\t|\ttype\t\t|\ttotal orders\t|\tBTC volume\t|\tAVG rate\n");
    printf("====================================================================================================================================\n");
    while ($fila = mysql_fetch_array($result, MYSQL_NUM)) {
        printf("%s\t\t|\t%d\t|\t%s\t\t|\t%d\t\t|\t%.8f\t|\t%.8f\n", $fila[0], $fila[1], $fila[2], $fila[3], $fila[4], $fila[5]);  
    }
}


/**
*   Show total order of sell and buy by MONTH, HOUR and each $minute minutes
*/
function get_by_minute($conn, $TABLE, $pair, $minute = 4, $LIMIT = 5){
    $SECONDS = $minute * 60;
    echo "\n\n";
    echo "Show total order of sell and buy by $minute minutes\n";
    $sql = "SELECT MONTHNAME(date) month, HOUR(date) hour, MINUTE(date) as minute, 'sell' as type, COUNT(type) as total_orders, sum(total) as volume_btc, AVG(`rate`) as avg_rate, DAY(`date`) as day FROM `".$TABLE."` WHERE market='".$pair."' AND type='sell' GROUP BY MONTH(date), DAY(date), HOUR(date), UNIX_TIMESTAMP(date) DIV $SECONDS
    UNION
    SELECT MONTHNAME(date) month, HOUR(date) hour, MINUTE(date) as minute, 'buy' as type , COUNT(type) as total_orders, sum(total) as volume_btc, AVG(`rate`) as avg_rate, DAY(`date`) as day FROM `".$TABLE."` WHERE market='".$pair."' AND type='buy' GROUP BY MONTH(date), HOUR(date), UNIX_TIMESTAMP(date) DIV $SECONDS ORDER BY month, day DESC, hour DESC, minute DESC, type LIMIT $LIMIT;";
    echo $sql;
    echo "\n\n";
    $result = mysql_query( $sql, $conn );

    if(! $result ) {
      echo mysql_error()."\n";
    }

    printf("month\t\t|\thour\t|\tminute\t|\ttype\t|\ttotal orders\t|\tBTC volume\t|\tAVG rate\n");
    printf("===================================================================================================================================\n");
    while ($fila = mysql_fetch_array($result, MYSQL_NUM)) {
        printf("%s\t\t|\t%d\t|\t%d\t|\t%s\t|\t%d\t\t|\t%.8f\t|\t%.8f\n", $fila[0], $fila[1], $fila[2], $fila[3], $fila[4], $fila[5], $fila[6]);  
    }
}

/**
*   Show total order of sell and buy by MONTH
*/
function get_orders_by_month($conn, $DATABASE, $TABLE, $pair){
    echo "\n";
    echo "Show total order of sell and buy by MONTH\n";
    $sql = "SELECT MONTHNAME(date) month, 'sell' as type, COUNT(type) as total_orders, sum(total) as volume_btc FROM `".$TABLE."` WHERE market='".$pair."' AND type='sell' GROUP BY MONTH(date) UNION
    SELECT MONTHNAME(date) month, 'buy' as type , COUNT(type) as total_orders, sum(total) as volume_btc FROM `".$TABLE."` WHERE market='".$pair."' AND type='buy' GROUP BY MONTH(date) ORDER BY month, type;
    ;";
    echo $sql;
    echo "\n\n";
    $result = mysql_query( $sql, $conn );

    if(! $result ) {
      echo mysql_error()."\n";
    }

    printf("month\t\t|\ttype\t\t|\ttotal orders\t|\tBTC volume\n");
    printf("===========================================================================================================\n");
    while ($fila = mysql_fetch_array($result, MYSQL_NUM)) {
        printf("%s\t\t|\t%s\t\t|\t%d\t\t|\t%.8f\n", $fila[0], $fila[1], $fila[2], $fila[3]);  
    }
}

if(count($argv) != 2){
    echo "usage ".$argv[0]." MARKET\n";
    echo "\texample: ".$argv[0]." BTC_ETH\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $DATABASE = "poloniex";
    $TABLE = "trade_history";
    echo "Market: ". $pair;
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

echo "connecting... ";
$conn =  mysql_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS);
if (!$conn) {
    die('No pudo conectarse: ' . mysql_error());
}
echo 'Conectado satisfactoriamente'."\n";


/**
 * Public API
 */

// Market

for(;;)
{
    $result = $api->get_trade_history($pair);

    $values_array = array();
    foreach($result as $k => $value){
        $values_array[] = "('".$pair."', ".$value['globalTradeID'].", ".$value['tradeID'].", '" . $value['date'] . "', '".$value['type']."', " . (double)$value['rate'] .", ". (double)$value['amount'] .", ". (double)$value['total'] .")";
    }

    /* insert ignoring duplicated entries */
    $sql = 'INSERT IGNORE INTO '.$TABLE.' '.
          '(`market`, `globalTradeID`, `tradeID`, `date`, `type`, `rate`, `amount`, `total`) '.
          'VALUES '. join(",", $values_array);

    mysql_select_db($DATABASE);
    $retval = mysql_query( $sql, $conn );

    if(! $retval ) {
        echo mysql_error()."\n";
    }

    echo "Entered data successfully\n";

    echo "\n";
    get_orders_by_hour($conn, $DATABASE, $TABLE, $pair);

    echo "\n";
    get_last_orders($conn, $DATABASE, $TABLE, $pair);
    
    /* show by last 5 $MINUTE minutes */
    //get_by_minute($conn, $TABLE, $pair, 5, 10);

    sleep(3);
}

mysql_close($conn);
