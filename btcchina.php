<?php
  function sign($method, $params = array()){
 
        $accessKey = "" // Your access key;
        $secretKey = "" // Your secret key;
 
        $mt = explode(' ', microtime());
        $ts = $mt[1] . substr($mt[0], 2, 6);
 
        $signature = http_build_query(array(
            'tonce' => $ts,
            'accesskey' => $accessKey,
            'requestmethod' => 'post',
            'id' => 1,
            'method' => $method,
            'params' => implode(',', $params),
        ));
        $signature = urldecode($signature);
 
        $hash = hash_hmac('sha1', $signature, $secretKey);
 
        return array(
            'ts' => $ts,
            'hash' => $hash,
            'auth' => base64_encode($accessKey.':'. $hash),
        );
    }
 
    function request($method, $params)
    {
        $sign = sign($method, $params);
 
        $options = array( 
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . $sign['auth'],
                'Json-Rpc-Tonce: ' . $sign['ts'],
            ),
        );
 
        $postData = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id' => 1,
        ));
 
        $headers = array(
                'Authorization: Basic ' . $sign['auth'],
                'Json-Rpc-Tonce: ' . $sign['ts'],
            );        
        $ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 
'Mozilla/4.0 (compatible; BTC China Trade Bot; '.php_uname('a').'; PHP/'.phpversion().')'
);
curl_setopt($ch, CURLOPT_URL, 'https://api.btcchina.com/api_trade_v1.php');
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
 
// run the query
$res = curl_exec($ch);
return $res;
/**/
      }

function get_market()
{
    $result = json_decode(request('getMarketDepth2', array(1)), true);
    return $result;
}

function get_last_price()
{
    $contents = file_get_contents("https://vip.btcchina.com/");
    $text_ini = "			<div class=\"span3 ticker-box\">
				<div class=\"last_price\">
					<div class=\"number\">¥";
    $ini = strpos($contents, $text_ini);
    $fin = strpos($contents, "</div>", $ini);
    $last_price = substr($contents, $ini+strlen($text_ini), $fin-$ini-strlen($text_ini));
    $last_price = str_replace(",", "", $last_price);

    return (float)$last_price;
}

function check_order()
{
    sleep(1);
    $foo = true;

    $result = json_decode(request('getOrders', array()), true);
    if( isset($result['result']['order']) && !empty($result['result']['order']) )
    {
        foreach($result['result']['order'] as $v)
        {
            if( ($v['status'] == "pending") || ($v['status'] == "open") )
            {
                $foo = false;
                json_decode(request('cancelOrder', array((int)$v['id'])), true);
            }
        }

        return $foo;
    }

    return $foo;
}

function sell_btc($fh, $last_price, $trad_btc)
{
    fwrite($fh, "Vende $trad_btc BTC\n");
    $result = json_decode(request('sellOrder2', array((float)$last_price, $trad_btc)), true);
    if( !empty($result) )
    {
        if(isset($result['error']))
        {
            fwrite($fh, "code: ".$result['error']['code']." - message: ".$result['error']['message']."\n");
            return false;
        }

        fwrite($fh, "Order id: ". $result['result'] ."\n");
        return true;
    }

    return false;
}

function buy_btc($fh, $last_price, $trad_btc)
{
    fwrite($fh, "Compra $trad_btc BTC\n");
    $result = json_decode(request('buyOrder2', array((float)$last_price, $trad_btc)), true);
    if( !empty($result) )
    {
        if(isset($result['error'])){
            fwrite($fh, "code: ".$result['error']['code']." - message: ".$result['error']['message']."\n");
            return false;
        }

        fwrite($fh, "Order id: ". $result['result'] ."\n");
        return true;
    }

    return false;
}

function get_amount()
{
    $result = json_decode(request('getAccountInfo', array()), true);

    $array['BTC'] = $result['result']['balance']['btc']['amount'];
    $array['CNY'] = $result['result']['balance']['cny']['amount'];

    return $array;
}

function get_open_orders()
{
    $result = json_decode(request('getOrders', array(true)), true);
    return $result;
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

function get_total_btc($amount, $last_trade_price)
{
    $amount_btc = (float)$amount['BTC'];
    $amount_cnybtc = (float)truncate($amount['CNY']/$last_trade_price, 8);
    return $amount_btc+$amount_cnybtc;
}

/**
*   Restart last_trade_price
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

$trade_file = "btcchina.trade";
$trade_log = "btcchina.log";
$fee = 0.003; // 0.30% fee 

$sleep_time = 60;

/* open trade log*/
$fh = fopen($trade_log, 'a') or die("can't open file");

/* data for trade */
$trad_btc = 0.001;              // BTC to sell/buy
$limit_btc = 0.001;             // Limit to sell/buy
if($trad_btc < $limit_btc) $trad_btc=$limit_btc;

$umbral = 0.007+$fee;           // Maximum trade amount in %

$last_trade_price = get_last_trade_price($trade_file, 0);

// Get amount
$amount = get_amount();
$amount_btc = $amount['BTC'];
$amount_cny = $amount['CNY'];

fwrite($fh, "\n==========\n");
fwrite($fh, "Last trad price: ".$last_trade_price."\n");
fwrite($fh, "Total BTC = ".$amount_btc."\n");
fwrite($fh, "Total CNY = ".$amount_cny."\n");

$last_trade_time = time();
{
    $last_trade_price = update_last_trade($last_trade_price, $last_trade_time, ($amount_btc >= $trad_btc));
    

    $last_price = get_market();

    if( !empty($last_price['result']) )
    {       
        // Get total amount and amount to buy BTC
        $buy_btc = truncate(($amount['CNY']/$last_price['result']['market_depth']['bid'][0]['price']), 8);
        $buy_btc -= $buy_btc * $fee;

        if( ($amount_btc >= $trad_btc) && ( ($last_trade_price*($umbral+1)) < $last_price['result']['market_depth']['bid'][0]['price'] ) )
        {       

            fwrite($fh, "\n==========\n");
            $res = sell_btc($fh, $last_price['result']['market_depth']['bid'][0]['price'], $trad_btc);
            sleep(10);

            if($res)
            {
                /* update last trade */
                $last_trade_price = $last_price['result']['market_depth']['bid'][0]['price'];
                set_last_trade_price($trade_file, $last_trade_price);

                fwrite($fh, "Sell succesful\n");
                fwrite($fh, "trad price: ".$last_trade_price." bid(sell price)\n");
                $last_trade_time = time();
                fwrite($fh, "date: ". date('d/m/Y h:i:s', $last_trade_time) ."\n");

                // Actualizar amount
                $amount = get_amount();
                $amount_btc = $amount['BTC'];
                $amount_cny = $amount['CNY'];
                fwrite($fh, "Total BTC = ".$amount_btc."\n");
                fwrite($fh, "Total CNY = ".$amount_cny."\n");
                fwrite($fh, "Total SUM BTC = ".get_total_btc($amount, $last_trade_price)."\n");

            }else{
                fwrite($fh, "Order not processed\n");
            }
        }
        elseif( ( ($buy_btc >= $trad_btc) && ($last_trade_price-($last_trade_price*$umbral)) > $last_price['result']['market_depth']['ask'][0]['price']) )
        {
            fwrite($fh, "\n==========\n");
            $res = buy_btc($fh, $last_price['result']['market_depth']['ask'][0]['price'], $trad_btc);
            sleep(10);
         
            if($res)
            {
                $last_trade_price = $last_price['result']['market_depth']['ask'][0]['price'];
                set_last_trade_price($trade_file, $last_trade_price);

                fwrite($fh, "Buy succesful\n");
                fwrite($fh, "trad price: ".$last_trade_price." ask(buy price)\n");
                $last_trade_time = time();
                fwrite($fh, "date: ". date('d/m/Y h:i:s', $last_trade_time) ."\n");


                $amount = get_amount();
                $amount_btc = $amount['BTC'];
                $amount_cny = $amount['CNY'];
                fwrite($fh, "Total BTC = ".$amount_btc."\n");
                fwrite($fh, "Total CNY = ".$amount_cny."\n");
                fwrite($fh, "Total SUM BTC = ".get_total_btc($amount, $last_trade_price)."\n");
            }else{
                fwrite($fh, "Order not processed\n");
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
