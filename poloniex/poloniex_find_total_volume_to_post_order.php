<?php
/**
*   get the total volume necesary to reach a price
*/

include 'APIPoloniex.php';

function print_r2($val){
    print_r($val);
}

if(count($argv) != 4){
    echo "usage ".$argv[0]." MARKET PRICE PRINT_PRICES_EXCEED_VOLUME\n";
    echo "\texample: ".$argv[0]." BTC_ETH 0.02501 1000\n";
    exit(-1);
}else{
    $pair = $argv[1];
    $price = $argv[2];
    $print_volume = $argv[3];
    $total = 100000;
 
    echo "Market: ". $pair;
    echo "\n";
}

/**
 * PRIVATE API
 */
$api = new APIPoloniex("", "");

$result = $api->get_ticker($pair);
$last = $result['last'];
$buy = true;
if($price > $last){
    $buy = false;
}


$result = $api->get_order_book($pair, $total);
if(isset($result['error'])){
    print_r($result);
    exit(-1);
}
if(empty($result)) {
    echo "Empty data.\n";
    exit(-1);
}

$volume = 0.0;
foreach($result as $type => $orders) {
    if(is_array($orders)){
            foreach($orders as $value2){
                $rate = $value2[0];
                $amount = $value2[1];
                if( ( (!$buy && $type == "asks") || ($buy && $type == "bids") ) && ($amount >= $print_volume) ){
                    //printf("volume '%24.8f'\tat rate\t'%24.8f'\n", $amount, $rate);                
		    printf("\tamount: %12.8f\trate: %12.8f\n", $amount, $rate);
                }
                if((!$buy && $type == "asks" && $rate >= $price) || ($buy && $type == "bids" && $rate <= $price)) {
                    printf("total volume orders to '%s' at rate '%0.8f' are:\t'%0.8f'\n", ($type == "bids") ? 'BUY' : 'SELL', $rate, $volume);
                    exit(0);                    
                }            

                if( (!$buy && $type == "asks") || ($buy && $type == "bids") ){
                    $volume = bcadd($volume, $amount, 8);
                }
            }      
    }
}

?>
