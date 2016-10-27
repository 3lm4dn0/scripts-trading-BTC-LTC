<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}

$FROM_DATE = new DateTime($argv[2]);
$DATABASE = "poloniex";
$TABLE = "user_balances";

echo "Database: ". $DATABASE;
echo "\n";
echo "Table: ". $TABLE;
echo "\n";

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
$start = $FROM_DATE->getTimestamp();
$end = time();
$result = $api->get_complete_balances();

// Create SQL:
$values_array = array();
foreach($result as $coin => $value){
    if((double)$value['btcValue'] > 0.0) {
        $values_array[] = "('" . $coin . "', " . (double)$value['available'] . ", " . (double)$value['onOrders'] . ", " . (double)$value['btcValue'] . ")";
    }
}

mysql_select_db($DATABASE);

/* truncate table */
$sql = "TRUNCATE TABLE $TABLE";
$retval = mysql_query( $sql, $conn );
if(! $retval ) {
    echo mysql_error()."\n";
}

/* insert ignoring duplicated entries */
$sql = 'INSERT INTO '.$TABLE.' '.
    '(coin, avaliable, onOrders, btcValue) '.
    'VALUES '. join(",", $values_array);

$retval = mysql_query( $sql, $conn );
if(! $retval ) {
    echo mysql_error()."\n";
}

echo "Entered data successfully\n";

mysql_close($conn);