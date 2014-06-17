<?php
include 'API.php';

/**
 * API from Bittrex Exchange version 1.1
 *
 * @link https://bittrex.com/Home/Api
 *      
 * @author d. albela
 *        
 */
class APIBittrex extends API {
	private $url;
	function __construct($key, $apisecret) {
		parent::__construct ( $key, $apisecret );
		
		$this->url = "https://bittrex.com/api/";
	}
	
	/**
	 * Override original method request_data
	 *
	 * @see API::request_data()
	 */
	function request_data($method, $sign = false, $params = array(), $version = 'v1.1') {
		$array = array ();
		$uri = $this->url . $version . $method;
		
		$separator = '?';

		/* add params */
		if (! empty ( $params )) {
			foreach ( $params as $k => $v ) {
				$uri .= $separator . $k . "=" . $v;
				$separator = "&";
			}
		}
		
		/* if not public sign key and secret */
		if ($sign) {
			$nonce = time ();
			$uri .= $separator . 'apikey=' . $this->apikey. '&nonce=' . $nonce;
			$sign = hash_hmac ( 'sha512', $uri, $this->apisecret );
			$array = array (
					'apisign:' . $sign 
			);			
		}
		
		$ch = curl_init ( $uri );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $array );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$execResult = curl_exec ( $ch );
		
		return json_decode( $execResult, true);
	}
	
	/**
	 * Used to get the open and available trading markets at Bittrex along with other meta data.
	 *
	 * @return array <NULL, mixed>
	 */
	function get_markets() {
		return $this->request_data ( '/public/getmarkets' );
	}
	
	/**
	 * Used to get all supported currencies at Bittrex along with other meta data.
	 *
	 * @return array <NULL, mixed>
	 */
	function get_currencies() {
		return $this->request_data ( '/public/getcurrencies' );
	}
	
	/**
	 * Used to get the current tick values for a market.
	 *
	 * @param string $market
	 *        	a string literal for the market (ex: BTC-LTC)
	 * @return array <NULL, mixed>
	 */
	function get_ticker($market) {
		return $this->request_data ( '/public/getticker', false, array (
				'market' => $market
		) );
	}
	
	/**
	 * Used to get the last 24 hour summary of all active exchanges
	 *
	 * @return array <NULL, mixed>
	 */
	function get_market_summaries() {
		return $this->request_data ( '/public/getmarketsummaries' );
	}
	
	/**
	 * Used to get retrieve the orderbook for a given market
	 *
	 * @param string $market
	 *        	required a string literal for the market (ex: BTC-LTC).
	 * @param string $type
	 *        	'both' | 'ask' | 'buy'
	 * @param int $depth
	 *        	optional defaults to 20 - how deep of an order book to retrieve. Max is 100.
	 * @return array <NULL, mixed>
	 */
	function get_order_book($market, $type, $depth = 20) {
		return $this->request_data ( '/public/getorderbook', false, array (
				'market' => $market,
				'type' => $type,
				'depth' => $depth 
		) );
	}
	
	/**
	 * Used to retrieve the latest trades that have occured for a specific market.
	 *
	 *
	 * @param string $market        	
	 * @param int $count        	
	 * @return array <NULL, mixed>
	 */
	function get_market_history($market, $count = 20) {
		return $this->request_data ( '/public/getmarkethistory', false, array (
				'market' => $market,
				'count' => $count 
		) );
	}
	
	/**
	 * Used to place a buy order in a specific market.
	 * Use buylimit to place limit orders
	 * and buymarket to place market orders. Make sure you have the proper permissions
	 * set on your API keys for this call to work
	 *
	 * @param string $market
	 *        	required a string literal for the market (ex: BTC-LTC)
	 * @param float $quantity
	 *        	required the amount to purchase
	 * @param float $rate
	 *        	required the rate at which to place the order. this is not needed for market orders
	 * @return array <NULL, mixed>
	 */
	function buy_limit($market, $quantity, $rate) {
		$array = array (
				'market' => $market,
				'quantity' => $quantity,
				'rate' => $rate 
		);
		
		return $this->request_data ( '/market/buylimit', true, $array );
	}
	
	/**
	 * Used to place a buy order in a specific market.
	 * Use buylimit to place limit orders
	 * and buymarket to place market orders. Make sure you have the proper permissions
	 * set on your API keys for this call to work
	 *
	 * @param string $market
	 *        	required a string literal for the market (ex: BTC-LTC)
	 * @param float $quantity
	 *        	required the amount to purchase
	 * @return array <NULL, mixed>
	 */
	function buy_market($market, $quantity) {
		$array = array (
				'market' => $market,
				'quantity' => $quantity 
		);
		
		return $this->request_data ( '/market/buymarket', true, $array );
	}
	
	/**
	 * Used to place an sell order in a specific market.
	 *
	 * Use selllimit to place limit orders and sellmarket to place market orders.
	 * Make sure you have the proper permissions set on your API keys for this call to work
	 *
	 * @param string $market
	 *        	required a string literal for the market (ex: BTC-LTC)
	 * @param float $quantity
	 *        	required the amount to purchase
	 * @param float $rate
	 *        	required the rate at which to place the order. this is not needed for market orders
	 * @return array <NULL, mixed>
	 */
	function sell_limit($market, $quantity, $rate) {
		$array = array (
				'market' => $market,
				'quantity' => $quantity,
				'rate' => $rate 
		);
		
		return $this->request_data ( '/market/selllimit', true, $array );
	}
	
	/**
	 * Used to place an sell order in a specific market.
	 *
	 * Use selllimit to place limit orders and sellmarket to place market orders.
	 * Make sure you have the proper permissions set on your API keys for this call to work
	 *
	 * @param string $market        	
	 * @param float $quantity        	
	 * @param float $rate        	
	 * @return array <NULL, mixed>
	 */
	function sell_market($market, $quantity) {
		$array = array (
				'market' => $market,
				'quantity' => $quantity 
		);
		
		return $this->request_data ( '/market/sellmarket', true, $array);
	}
	
	/**
	 * Used to cancel a buy or sell order.
	 * Return true if success
	 *
	 * @param int $uuid
	 *        	required uuid of buy or sell order
	 * @return boolean return true if success
	 */
	function cancel_order($uuid) {
		$array = array (
				'uuid' => $uuid 
		);
		
		return ($this->request_data ( '/market/cancel', true, $array ) != null);
	}
	
	/**
	 * Get all orders that you currently have opened.
	 * A specific market can be requested
	 *
	 * @param array $market
	 *        	a string literal for the market (ie. BTC-LTC)
	 * @return array <NULL, mixed>
	 */
	function get_open_orders($market = '') {
		$array = array ();
		if (! empty ( $market )) {
			$array = array (
					'market' => $market 
			);
		}
		
		return $this->request_data ( '/market/getopenorders', true, $array, 'v1' );
	}
	
	/**
	 * Used to retrieve all balances from your
	 *
	 * @return array <NULL, mixed>
	 * @deprecated
	 *
	 *
	 *
	 *
	 */
	function get_balances() {
		return $this->request_data ( '/account/getbalances', true );
	}
	
	/**
	 * Used to retrieve the balance from your account for a specific currency.
	 * TODO: There is a problem with API 1.1 with this request
	 *
	 * @param string $currency
	 *        	required a string literal for the currency (ex: LTC)
	 * @return array <NULL, mixed>
	 */
	function get_balance($currency) {
		$array = array (
				'apikey' => $this->apikey,
				'currency' => $currency 
		);
		
		return $this->request_data ( '/account/getbalance', false, $array , 'v1');
	}
	
	/**
	 * Used to retrieve an address for a specific currency.
	 *
	 *
	 * @param string $currency
	 *        	required a string literal for the currency (ie. BTC)
	 * @return array <NULL, mixed>
	 */
	function get_deposit_address($currency) {
		$array = array (
				'apikey' => $this->apikey,
				'currency' => $currency 
		);
		
		return $this->request_data ( '/account/getdepositaddress', false, $array );
	}
	
	/**
	 * Used to withdraw funds from your account.
	 * note: please account for txfee.
	 *
	 * @param string $currency
	 *        	required a string literal for the currency (ie. BTC)
	 * @param float $quantity
	 *        	required the quantity of coins to withdraw
	 * @param string $address
	 *        	required the address where to send the funds.
	 * @return array <NULL, mixed>
	 */
	function withdraw($currency, $quantity, $address) {
		$array = array (
				'currency' => $currency,
				'quantity' => $quantity,
				'address' => $address 
		);
		
		return $this->request_data ( '/account/withdraw', $array );
	}
	
	/**
	 * Used to retrieve your order history.
	 *
	 * @param string $market
	 *        	a string literal for the market (ie. BTC-LTC). If ommited, will return for all markets
	 * @param int $count
	 *        	the number of records to return
	 * @return array <NULL, mixed>
	 */
	function get_order_history($market, $count = 20) {
		return $this->request_data ( '/account/getorderhistory', true, array (
				'market' => $market,
				'count' => $count 
		));
	}
	
	/**
	 * Used to retrieve your withdrawal history.
	 *
	 *
	 * @param string $currency
	 *        	a string literal for the currecy (ie. BTC). If ommited, will return for all currencies
	 * @param int $count
	 *        	the number of records to return
	 * @return array <NULL, mixed>
	 */
	function get_withdrawal_history($currency, $count = 20) {
		return $this->request_data ( '/account/getwithdrawalhistory', true, array (
				'currency' => $currency,
				'count' => $count 
		) );
	}
	
	/**
	 * Used to retrieve your deposit history.
	 *
	 * @param string $currency
	 *        	a string literal for the currecy (ie. BTC). If ommited, will return for all currencies
	 * @param int $count
	 *        	the number of records to return
	 * @return array <NULL, mixed>
	 */
	function get_deposit_history($currency, $count = 20) {
		return $this->request_data ( '/account/getdeposithistory', true, array (
				'currency' => $currency,
				'count' => $count 
		) );
	}
}

?>
