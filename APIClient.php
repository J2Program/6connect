<?php
namespace api;

define('API_STATUS_SUCCESS',1);
define('API_STATUS_ERROR', 0);

class APIClient {

	const API_STATUS_SUCCESS = 1;
	const API_STATUS_ERROR = 0;

	public $url;
	public $apiResponse;
	public $rawResponse;

	private $VERSION = 1;
	private $apiBaseUrl;
	private $apiKey;
	private $secretKey;
	private $instanceUrl;

	public function __construct($instanceUrl, $apiKey , $secretKey){

		$this->instanceUrl	= $instanceUrl;
		$this->apiBaseUrl	= "$instanceUrl/api/v$this->VERSION/api.php";
		$this->apiKey		= $apiKey;
		$this->secretKey	= $secretKey;
	}

	public function getRequestUrl($target, $action, $reqParams){

		$queryString = 'target=' . urlencode($target);
		$queryString .= '&action=' . urlencode($action);

		foreach($reqParams as $k => $v){
			$v = trim($reqParams[$k]);
			$queryString .= '&' . urlencode($k) . '=' . urlencode(trim($v));
		}

		$queryString .= '&apiKey=' . urlencode($this->apiKey);

		//If $apiKey is not specified, create un-hashed URL for local, already-authenticated use
		if($this->apiKey != ''){
			$hash = APIClient::getHash($queryString, $this->secretKey);
			$queryString .= "&hash=$hash";
		}

		return "$this->apiBaseUrl?$queryString";
	}

	public function sendRequest($target, $action, $reqParams){

		$this->url = APIClient::getRequestUrl($target, $action, $reqParams);

		//Use cURL to send API request
		$session = curl_init($this->url);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, 0);

		$this->rawResponse = curl_exec($session);
		$this->response = new APIResponse();

		$curlInfo = curl_getinfo($session);

		$this->response->httpStatus = $curlInfo['http_code'];

		if( $curlInfo['http_code'] != 200 || $this->rawResponse === false ){

			$this->response->status = API_STATUS_ERROR;
			$this->response->httpStatus = $curlInfo['http_code'];

			if( $curlInfo['http_code'] == 404 ){
				$this->response->message = 'HTTP 404 Not Found: Please make sure the "instanceUrl" in api-config.php is correct';
			}

			if( $curlInfo['http_code'] == 401 ){
				$this->response->message = 'HTTP 401 Unauthorized: Please make sure the "apiKey" and "apiSecretKey" values in api-config.php are correct';
			}

			return $this->response;
		}

		curl_close($session);

		//We want an associative array, not an object
		$obj = json_decode($this->rawResponse, true);

		if($obj === null || $obj === false){
			$error = json_last_error();
			switch($error){
				case JSON_ERROR_DEPTH: 		$msg = 'The maximum stack depth has been exceeded'; break;
				case JSON_ERROR_CTRL_CHAR: 	$msg = 'Control character error, possibly incorrectly encoded'; break;
				case JSON_ERROR_SYNTAX: 	$msg = 'Syntax error'; break;
			}
			$obj = array('success'=>0, 'message'=>"JSON Error: $msg");
		}

		$this->response->status = $obj['success'] == 1 ? API_STATUS_SUCCESS : API_STATUS_ERROR;
		$this->response->message = $obj['message'];
		$this->response->data = !empty($obj['data']) ? $obj['data'] : null;

		return $this->response;
	}

	public static function getHash($value, $secretKey){
		return base64_encode(hash_hmac('sha256', $value, $secretKey, TRUE));
	}
}


class APIResponse {

	public $httpStatus;
	public $status;
	public $message;
	public $data;
}
