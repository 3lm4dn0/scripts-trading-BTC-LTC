<?php

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}


/**
 * PRIVATE API
 */
$api = new APIPoloniex("", "");

$result = $api->get_ticker();
if(isset($result['error'])){
    print_r($result);
    exit(-1);
}

if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

$currencies = array();
foreach($result as $key => $value){
    $currencies[] = $key;
}

print_r($currencies);
echo join(", ", $currencies);
