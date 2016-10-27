<?php

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

if(count($argv) != 3){
    echo "usage ".$argv[0]." MARKET MINUTES\n";
    echo "\texample: ".$argv[0]." BTC_ETH 5\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $MINUTE = $argv[2];
    $DATABASE = "poloniex";
    $TABLE = "trade_history";
 
    echo "Market: ". $pair;
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
echo 'Conectado satisfactoriamente'."\n";


/**
 * show data
 */
mysql_select_db($DATABASE);

/* show by last 10 $MINUTE minutes */
get_by_minute($conn, $TABLE, $pair, $MINUTE, 10);

mysql_close($conn);
