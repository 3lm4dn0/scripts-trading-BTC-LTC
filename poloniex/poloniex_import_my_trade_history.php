<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 3){
    echo "usage ".$argv[0]." MARKET FROM_DATE\n";
    echo "\texample: ".$argv[0]." BTC_ETH 2016-03-01\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $FROM_DATE = new DateTime($argv[2], new DateTimeZone('Europe/Madrid'));
    $DATABASE = "poloniex";
    $TABLE = "user_trade_history";
 
    echo "Market: ". $pair;
    echo "\n";
    echo "Database: ". $DATABASE;
    echo "\n";
    echo "Table: ". $TABLE;
    echo "\n";
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

/**
 * PRIVATE API
 */
$api = new APIPoloniex($key, $apisecret);
$start = $FROM_DATE->getTimestamp();
$end = time();
$result = $api->get_my_trade_history($pair, $start, $end);

if(isset($result['error'])){
    print_r($result);
    exit(-1);
}

if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

echo "\n\t`market`, `globalTradeID`, `tradeID`, `date`, `rate`, `amount`, `total`, `fee`, `orderNumber`, `type`, `category`\n";
$values_array = array();
foreach ($result as $k => $value) {
    $values_array[] = "('" . $pair . "', " . $value['globalTradeID'] . ", " . $value['tradeID'] . ", '" . $value['date'] . "', " . (double)$value['rate'] . ", " . (double)$value['amount'] . ", " . (double)$value['total'] . ", " . (double)$value['fee'] . ", " . $value['orderNumber'] . ", '" . $value['type'] . "', '" . $value['category'] . "')";
}
print_r($values_array);

echo "connecting... ";
$conn =  mysql_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS);
if (!$conn) {
    die('No pudo conectarse: ' . mysql_error());
}
echo 'Conectado satisfactoriamente'."\n";

/* insert ignoring duplicated entries */
$sql = 'INSERT IGNORE INTO ' . $TABLE . ' ' .
    '(`market`, `globalTradeID`, `tradeID`, `date`, `rate`, `amount`, `total`, `fee`, `orderNumber`, `type`, `category`) ' .
    'VALUES ' . join(",", $values_array);

mysql_select_db($DATABASE);
$retval = mysql_query($sql, $conn);
if (!$retval) {
    echo mysql_error() . "\n";
}

echo "Entered data successfully\n";

mysql_close($conn);
