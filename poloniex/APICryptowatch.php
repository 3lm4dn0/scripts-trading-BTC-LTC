<?php
        // FINAL TESTED CODE - Created by Compcentral
       
        // NOTE: currency pairs are reverse of what most exchanges use...
        //       For instance, instead of XPM_BTC, use BTC_XPM
 
        class APICryptowatch {
                protected $public_url = "https://cryptowat.ch";
               
                public function __construct() {
                }
               
                protected function retrieveJSON($URL) {
                    // generate the extra headers
                    $headers = array(
                        'Connection: keep-alive',
                        'DNT: 1',
                        'Cache-Control: max-age=0',
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Upgrade-Insecure-Requests: 1'
                    );

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:38.0) Gecko/20100101 Firefox/38.0 Iceweasel/38.7.1');
                    curl_setopt($ch, CURLOPT_URL, $URL);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt( $ch, CURLOPT_ENCODING, "gzip");
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                    // run the query
                    $return = curl_exec($ch);
                    $info = curl_getinfo($ch);

                    while ($return === false){
                        printf("Trying reconnect to '%s'\n", $URL);
                        sleep(60);
                        $return = curl_exec($ch);
                    }

                    curl_close($ch);
                    $json = json_decode($return, true);
                    if (!$json){
                        throw new Exception('Invalid data: '.$return);
                    }

                    return $json;
                }

                private function format_data_chart($data_chart, $period){
                    $result = array();
                    foreach($data_chart as $value){
                        $data = explode(" ", $value);
                        $weightedAverage = bcdiv(bcadd($data[2], $data[3], 8), 2, 8);
                        $volume = bcmul($weightedAverage, $data[5], 8);
                        $result[] = array('date' => $data[0]-$period, 'open' => $data[1], 'high' => $data[2], 'low' => $data[3], 'close' => $data[4], 'quoteVolume' => $data[5], 'volume' => $volume, 'weightedAverage' => $weightedAverage);
                    }

                    return $result;
                }

                public function get_data_chart_poloniex_ethbtc($pair, $period = 60) {
                        $coins = explode("_", strtolower($pair));
                        $pair = $coins[1].$coins[0];
                        $URL = $this->public_url . '/poloniex/'.$pair.'.json';
                        $trades = $this->retrieveJSON($URL);

                        $valid_period_values = array(60, 300, 900, 1800, 7200, 14400, 86400);
                        if(!in_array($period, $valid_period_values)){
                            die("period error: valid values are 60, 300, 900, 1800, 7200, 14400, and 86400\n");
                        }
                        $data_chart = $trades[$period];

                        return $this->format_data_chart($data_chart, $period);
                }
        }
?>
