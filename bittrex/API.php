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
	protected function request_data($method, $sign = false, $params = array(), $version){
		
		return array();
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