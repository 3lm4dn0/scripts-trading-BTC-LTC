<?php

include 'APIBittrex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 2){
    echo "usage ".$argv[0]." MARKET\n";    
    echo "\texample: ".$argv[0]." BTC-ETH\n";
    echo "\texample: ".$argv[0]." all\n";
    exit(-1);
}else{
    $pair = $argv[1];
    if($pair == 'all'){
        $pair = '';
    }
    echo "Market: ". $pair;
    echo "\n";
}


$key = getenv('BIT_KEY');
$apisecret = getenv('BIT_PASS');
if(empty($key) || empty($apisecret)){
    echo "Set environment variables from Exchange API Key:\n";
    echo "export BIT_KEY=\"your key\"\n";
    echo "export BIT_PASS=\"your super big secret\"\n";
    echo "\n\nPlease be careful with this sensible data. Store your keys encrypted, protect your profile and allow only connections from your IP and do not allow withdrawals.\n";
    exit(-1);
}
$api = new APIBittrex($key, $apisecret);

/**
 * Account API
 */

/* get balances @deprecated */
$result = $api->get_open_orders($pair);
print_r2($result);
