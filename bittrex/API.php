<?php
class API {
	
	protected $apikey;
	protected $apisecret;
	
	public function __construct($key, $apisecret){
		$this->apikey = $key;
		$this->apisecret = $apisecret;		
	}
	
	/**
	 * Used to get data from an API REST
	 * 
	 * @param string $method
	 * @param boolean $sign
	 * @param array $params
	 * @return multitype|NULL
	 */
	protected function request_data($method, $sign = false, $params = array()){
		
		return array();
	}
	
	/**
	 * Used to place any order in a specific market.
	 *
	 * @param String $market  required 	a string literal for the market (ex: BTC-LTC)
	 * @param float $quantity required 	the amount to purchase
	 * @param float $rate     required 	the rate at which to place the order. this is not needed for market orders
	 * @return Ambigous <NULL, mixed>
	 */
	protected function operation($method, $params = array()){
		return $this->request_data ($method, true, $params);
	}
	
	/**
	 * Get the key
	 * 
	 * @return string
	 */
	public function getKey(){
		return $this->apikey;
	}
}

?>