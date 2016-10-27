<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
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
$result = $api->get_complete_balances();
if(isset($result['error'])){
    print_r($result);
    exit(-1);
}

if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

echo "Your balances\n";
foreach($result as $coin => $value) {
    $balance = (double)$value['btcValue'];
    if ($balance > 0.0) {
        echo $coin . ":\n";
        echo "\tavailable: " . $value['available'] . "\n";
        echo "\tonOrders: " . $value['onOrders'] . "\n";
        echo "\tbtcValue: " . $value['btcValue'] . "\n";
    }
}