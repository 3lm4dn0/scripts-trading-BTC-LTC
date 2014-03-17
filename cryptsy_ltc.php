<?php
function api_query($method, array $req = array()) {

        // API settings
        $key = ''; // your API-key
        $secret = ''; // your Secret-key
 
        $req['method'] = $method;
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1];
       
        // generate the POST data string
        $post_data = http_build_query($req, '', '&');

        $sign = hash_hmac("sha512", $post_data, $secret);
 
        // generate the extra headers
        $headers = array(
                'Sign: '.$sign,
                'Key: '.$key,
        );
 
        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Cryptsy API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
        }
        curl_setopt($ch, CURLOPT_URL, 'https://api.cryptsy.com/api');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
 
        // run the query
        $res = curl_exec($ch);

        if ($res === false) return false; //throw new Exception('Could not get reply: '.curl_error($ch));
        $dec = json_decode($res, true);
        if (!$dec) return false; //throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
        return $dec;
}

function truncate($zahl, $decimals="4")
{
    return floor($zahl*pow(10,$decimals))/pow(10,$decimals);
}
 
function get_last_trade_price($filename, $default=0)
{
    if(file_exists($filename))
    {
        return file_get_contents($filename);
    }else{
        return $default;
    }
}

function set_last_trade_price($filename, $trade_price)
{
    $fh = fopen($filename, 'w');
    fwrite($fh, "$trade_price");
    fclose($fh);
}

function get_total_coin($amount, $last_trade_price)
{
    $amount_coin = (float)$amount['LTC'];
    $amount_btc = (float)truncate($amount['BTC']/$last_trade_price, 8);
    return $amount_coin+$amount_btc;
}

function get_amount()
{
    $result = api_query("getinfo");

    if($result["success"])
    {
        $array['LTC'] = $result["return"]["balances_available"]["LTC"];
        $array['BTC'] = $result["return"]["balances_available"]["BTC"];
        return $array;
    }

    return false;
}

function get_market($id)
{
    $result = api_query("depth", array("marketid" => $id));
    if($result["success"])
    {
        $res['sell'] = $result["return"]["buy"][0][0];
        $res['buy'] = $result["return"]["sell"][0][0];
        return $res;
    }

    return false;
}

function cancel_marketorders($id)
{
    $result = api_query("cancelorder", array("orderid" => $id));

    return $result["success"];
}

function get_orders($id, $order_id, $trade_type, $trade_price, $quantity, $fh)
{
    // search order
    $total = 0;
    $last_tradeid = -1;
    for($i=0; $i<20; $i++)
    {
        sleep(10);

        //$result = api_query("myorders", array("marketid" => $id));
        $result = api_query("mytrades", array("marketid" => $id, "limit" => 1));

        if($result["success"])
        {
            if( ($result["return"][0]["tradetype"] == $trade_type) 
                && ($result["return"][0]["tradeprice"] == $trade_price) 
                && ($result["return"][0]["tradeid"] != $last_tradeid) )
            {
                $total += $result["return"][0]["quantity"];
            }

            if($total == $quantity)
            {
                return true;
            }

            $last_tradeid = $result["return"][0]["tradeid"];
        }
    }

    if($total>0)
    {
        fwrite($fh, "Partial: ". $trade_type ." amount ". $total ."\n");
        cancel_marketorders($order_id);
        return true;
    }

    // Delete order
    cancel_marketorders($order_id);
    return false;
}

function sell_coin($fh, $id, $last_price, $trad_coin)
{
    $result = api_query("createorder", array("marketid" => $id, "ordertype" => 'Sell', "quantity" => $trad_coin, "price" => $last_price));

    if($result["success"])
    {
        return get_orders($id, $result["orderid"], "Sell", $last_price, $trad_coin, $fh);
    }

    fwrite($fh, "Error: ". $res['error'] ."\n");
    return false;
}

/**
* Buy coin
*/
function buy_coin($fh, $id, $last_price, $trad_coin)
{
    $result = api_query("createorder", array("marketid" => $id, "ordertype" => 'Buy', "quantity" => $trad_coin, "price" => $last_price));

    if($result["success"])
    {
        return get_orders($id, $result["orderid"], "Buy", $last_price, $trad_coin, $fh);
    }

    fwrite($fh, "Error: ". $res['error'] ."\n");
    return false;
}

/**
*   Update last trade
*/
function update_last_trade($last_trade_price, $last_trade_time, $sell)
{
    // Dont pass 48 hours = 172800 seconds
    $actual = time() - $last_trade_time;
    if($actual < 172800)
    {
        return $last_trade_price;
    }

    /* if we can sell, it's that we bought all coins and the prices low. Restart last trade to zero */
    if($sell)
    {
        return 0;
    /* if we can not sell, it's that we sell all coins and the pricess hight. Restart last trade to hight value */
    }else{
        return 999999;
    }
}

try {

date_default_timezone_set('Europe/Madrid');

$trade_file = "cryptsy_ltc.trade";
$trade_log = "cryptsy_ltc.log";

/* open trade log*/
$fh = fopen($trade_log, 'a+') or die("can't open file");

$sleep_time = 60; // sleep time between check
$id_market = 3; // Id para mercado de LTC/BTC

// DEBUG
//$res = get_orders($id_market, 1);
//$res = get_market($id_market);
//var_dump($res);
//exit(0);
// DEBUG


/* data for trade */
$trad_coin = 0.1;              // Cuantos LTC compramos/vendemos. Como mínimo debe ser $limit_coin
$limit_coin = 0.01;             // límite de compra/venta LTC
if($trad_coin < $limit_coin) $trad_coin=$limit_coin;

// FEEs and minimum trade
$fee_sell = 0.003; // 0.30% fee 
$fee_buy = 0.002;
$umbral = 0.007;
$umbral_sell = $umbral+$fee_sell;
$umbral_buy = $umbral+$fee_buy;

$last_trade_price = get_last_trade_price($trade_file, 0);

$amount = get_amount();
$amount_coin = $amount['LTC'];
$amount_btc = $amount['BTC'];

fwrite($fh, "\n==========\n");
fwrite($fh, "Last trad price: ".$last_trade_price."\n");
fwrite($fh, "Total LTC = ".$amount_coin."\n");
fwrite($fh, "Total BTC = ".$amount_btc."\n");

$last_trade_time = time();
for(;;)
{
    $last_trade_price = update_last_trade($last_trade_price, $last_trade_time, ($amount_coin >= $trad_coin));

    $last_price = get_market($id_market);
    if( !$amount )
    {   
        $amount = get_amount();
    }   
    
    if( $amount && $last_price )

    {       
        $comprar_coin = truncate(($amount['BTC']/$last_price['buy']), 8);
        $comprar_coin -= $comprar_coin * $fee_buy;

        if( ($amount_coin >= $trad_coin) && ( ($last_trade_price*($umbral_sell+1)) < $last_price['sell'] ) )
        {
            $res = sell_coin($fh, $id_market, $last_price['sell'], $trad_coin);

            if($res)
            {
                $last_trade_price = $last_price['sell'];
                set_last_trade_price($trade_file, $last_trade_price);

                fwrite($fh, "\n==========\n");
                fwrite($fh, "Sell succesful\n");
                fwrite($fh, "trad price: ".$last_trade_price."\n");
                $last_trade_time = time();
                fwrite($fh, "date: ". date('d/m/Y h:i:s', $last_trade_time) ."\n");

                $amount = get_amount();
                $amount_coin = $amount['LTC'];
                $amount_btc = $amount['BTC'];
                fwrite($fh, "Total LTC = ".$amount_coin."\n");
                fwrite($fh, "Total BTC = ".$amount_btc."\n");
                fwrite($fh, "Total SUM LTC = ".get_total_coin($amount, $last_trade_price)."\n");
            }
        }

        elseif( ( ($comprar_coin >= $trad_coin) && ($last_trade_price-($last_trade_price*$umbral_buy)) > $last_price['buy']) )
        {
            $res = buy_coin($fh, $id_market, $last_price['buy'], $trad_coin);

            if($res)
            {
                $last_trade_price = $last_price['buy'];
                set_last_trade_price($trade_file, $last_trade_price);

                fwrite($fh, "\n==========\n");         
                fwrite($fh, "Buy succesful\n");
                fwrite($fh, "trad price: ".$last_trade_price."\n");
                $last_trade_time = time();
                fwrite($fh, "date: ". date('d/m/Y h:i:s', $last_trade_time) ."\n");

                $amount = get_amount();
                $amount_coin = $amount['LTC'];
                $amount_btc = $amount['BTC'];
                fwrite($fh, "Total LTC = ".$amount_coin."\n");
                fwrite($fh, "Total BTC = ".$amount_btc."\n");
                fwrite($fh, "Total SUM LTC = ".get_total_coin($amount, $last_trade_price)."\n");
            }
        }
    }

    sleep($sleep_time);
}


} 
catch (Exception $e) 
{
    echo $fh, "Error:".$e->getMessage();
} 

?>
