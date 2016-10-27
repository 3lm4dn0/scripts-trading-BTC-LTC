<?php
        // FINAL TESTED CODE - Created by Compcentral
       
        // NOTE: currency pairs are reverse of what most exchanges use...
        //       For instance, instead of XPM_BTC, use BTC_XPM
 
        class APIPoloniex {
                protected $api_key;
                protected $api_secret;
                protected $trading_url = "https://poloniex.com/tradingApi";
                protected $public_url = "https://poloniex.com/public";
               
                public function __construct($api_key, $api_secret) {
                        $this->api_key = $api_key;
                        $this->api_secret = $api_secret;
                }
                       
                private function query(array $req = array()) {
                        // API settings
                        $key = $this->api_key;
                        $secret = $this->api_secret;
                 
                        // generate a nonce to avoid problems with 32bit systems
                        $mt = explode(' ', microtime());
                        $req['nonce'] = $mt[1].substr($mt[0], 2, 6);
                 
                        // generate the POST data string
                        $post_data = http_build_query($req, '', '&');
                        $sign = hash_hmac('sha512', $post_data, $secret);
                 
                        // generate the extra headers
                        $headers = array(
                                'Key: '.$key,
                                'Sign: '.$sign,
                        );
 
                        // curl handle (initialize if required)
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:38.0) Gecko/20100101 Firefox/38.0 Iceweasel/38.7.1');
                        curl_setopt($ch, CURLOPT_URL, $this->trading_url);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
 
                        // run the query
                        $res = curl_exec($ch);

                        while ($res === false){
                            printf("Trying reconnect to '%s'\n", $this->trading_url);
                            sleep(60);
                            $res = curl_exec($ch);
                        }

                        curl_close($ch);
                        $dec = json_decode($res, true);
                        if (json_last_error() != JSON_ERROR_NONE){
                            return array('error' => 'Invalid data: '.$res);
                        }

                        return $dec;
                }
               
                protected function retrieveJSON($URL) {
                        $opts = array('http' => array('method'  => 'GET', 'timeout' => 10));
                        $context = stream_context_create($opts);
                        $feed = file_get_contents($URL, false, $context);
                        while ($feed === false){
                            printf("Trying reconnect to '%s'\n", $URL);
                            sleep(60);
                            $feed = file_get_contents($URL, false, $context);
                        }

                        $json = json_decode($feed, true);
                        if (json_last_error() != JSON_ERROR_NONE){
                            return array('error' => 'Invalid data: '.$feed);
                        }

                        return $json;
                }
               
                public function get_balances() {
                        return $this->query(
                                array(
                                        'command' => 'returnBalances'
                                )
                        );
                }

                public function get_complete_balances() {
                    return $this->query(
                        array(
                            'command' => 'returnCompleteBalances'
                        )
                    );
                }

                public function get_fee() {
                        return $this->query(
                                array(
                                        'command' => 'returnFeeInfo'
                                )
                        );
                }

               
                public function get_open_orders($pair) {               
                        return $this->query(
                                array(
                                        'command' => 'returnOpenOrders',
                                        'currencyPair' => strtoupper($pair)
                                )
                        );
                }
               
                public function get_my_trade_history($pair, $start = null, $end = null) {

                    $params = array(
                        'command' => 'returnTradeHistory',
                        'currencyPair' => strtoupper($pair)
                    );

                    if(!empty($start) && !empty($end)){
                        $params['start'] = $start;
                        $params['end'] = $end;
                    }

                    return $this->query($params);
                }
               
                public function buy($pair, $rate, $amount) {
                        return $this->query(
                                array(
                                        'command' => 'buy',    
                                        'currencyPair' => strtoupper($pair),
                                        'rate' => $rate,
                                        'amount' => $amount
                                )
                        );
                }
               
                public function sell($pair, $rate, $amount) {
                        return $this->query(
                                array(
                                        'command' => 'sell',   
                                        'currencyPair' => strtoupper($pair),
                                        'rate' => $rate,
                                        'amount' => $amount
                                )
                        );
                }

                public function move_order($order_number, $rate, $amount = null){
                    return $this->query(
                        array(
                            'command' => 'moveOrder',
                            'orderNumber' => $order_number,
                            'rate' => $rate,
                            'amount' => $amount
                        )
                    );
                }

                public function cancel_order($pair, $order_number) {
                        return $this->query(
                                array(
                                        'command' => 'cancelOrder',    
                                        'currencyPair' => strtoupper($pair),
                                        'orderNumber' => $order_number
                                )
                        );
                }
               
                public function withdraw($currency, $amount, $address) {
                        return $this->query(
                                array(
                                        'command' => 'withdraw',       
                                        'currency' => strtoupper($currency),                           
                                        'amount' => $amount,
                                        'address' => $address
                                )
                        );
                }

                public function get_chart_data($pair, $period = null, $start = null, $end = null) {

                        $params = '?command=returnChartData&currencyPair='.strtoupper($pair);
                        if(!empty($period)){
                            $params .= '&period='.$period;
                        }

                        if(!empty($start) && !empty($end)){
                            $params .= '&start='.$start . '&end=' . $end;
                        }                            

                        $trades = $this->retrieveJSON($this->public_url.$params);
                        return $trades;
                }
               
                public function get_trade_history($pair) {
                        $trades = $this->retrieveJSON($this->public_url.'?command=returnTradeHistory&currencyPair='.strtoupper($pair));
                        return $trades;
                }
               
                public function get_order_book($pair, $depth = 50) {
                        $orders = $this->retrieveJSON($this->public_url.'?command=returnOrderBook&currencyPair='.strtoupper($pair).'&depth=' . $depth);
                        return $orders;
                }
               
                public function get_volume() {
                        $volume = $this->retrieveJSON($this->public_url.'?command=return24hVolume');
                        return $volume;
                }
       
                public function get_ticker($pair = "ALL") {
                        $pair = strtoupper($pair);
                        $prices = $this->retrieveJSON($this->public_url.'?command=returnTicker');
                        if($pair == "ALL"){
                                return $prices;
                        }else{
                                $pair = strtoupper($pair);
                                if(isset($prices[$pair])){
                                        return $prices[$pair];
                                }else{
                                        return array();
                                }
                        }
                }

                public function get_currencies() {
                        return $this->retrieveJSON($this->public_url.'?command=returnCurrencies');
                }
               
                public function get_trading_pairs() {
                        $tickers = $this->retrieveJSON($this->public_url.'?command=returnTicker');
                        return array_keys($tickers);
                }
               
                public function get_total_btc_balance() {
                        $balances = $this->get_balances();
                        $prices = $this->get_ticker();
                       
                        $tot_btc = 0;
                       
                        foreach($balances as $coin => $amount){
                                $pair = "BTC_".strtoupper($coin);
                       
                                // convert coin balances to btc value
                                if($amount > 0){
                                        if($coin != "BTC"){
                                                $tot_btc += $amount * $prices[$pair];
                                        }else{
                                                $tot_btc += $amount;
                                        }
                                }
 
                                // process open orders as well
                                if($coin != "BTC"){
                                        $open_orders = $this->get_open_orders($pair);
                                        foreach($open_orders as $order){
                                                if($order['type'] == 'buy'){
                                                        $tot_btc += $order['total'];
                                                }elseif($order['type'] == 'sell'){
                                                        $tot_btc += $order['amount'] * $prices[$pair];
                                                }
                                        }
                                }
                        }
 
                        return $tot_btc;
                }
        }
?>
