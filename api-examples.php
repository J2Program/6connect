<?php
namespace api;
error_reporting(E_ALL);

if( php_sapi_name() !== 'cli' ){
	die('Script must be run from the command line');
}

require('APIClient.php');

require('api-config.php');

//Create a new APIClient object
$apiClient = new APIClient($config['instanceUrl'], $config['apiKey'], $config['apiSecretKey']);

//We will need this to delete the resource we create
$apiTestResourceId = '';


/**
 * API basic Hello World Test 
 */

echo "Testing the 6connect API...\n";

//Construct the parameters array for our request
$target = 'test';
$action = 'helloWorld';
$params = [
	'param1' => 100
];

sendRequestAndPrintResponse($apiClient, $target, $action, $params);


/**
 * Create a resource named API Test Resource
 */

echo "Creating \"API Test Resource\"\n";

$target = 'resource';
$action	= 'add';
$params = [
	'meta[type]'	=> 'entry',
	'meta[section]'	=> 'resource-holder',
	'meta[name]'	=> 'API Test Resource'
];

sendRequestAndPrintResponse($apiClient, $target, $action, $params);

$apiTestResourceId = $apiClient->response->data['id'];

/**
 * Add IPAM aggregate 213.0.0.0/20;
 */

echo "Adding aggregate 213.0.0.0/20\n";

$target = 'ipam';
$action = 'add';
$params = [
	'rir'		=> 'ARIN',
	'block'		=> '213.0.0.0/20',
	'code' 		=> 'api-test-block'
];

sendRequestAndPrintResponse($apiClient, $target, $action, $params);

/**
 * Update IPAM aggregate 213.0.0.0/20 adding the "VPN" and "Customer"  tags.
 * Custoemr and VPN tags are created as part of the default dataset. This call will fail
 * if they have been deleted.
 */

echo "Update aggregate 213.0.0.0/20 with Customer and VPN tags\n";

$target = 'ipam';
$action = 'update';
$params = [
	'rir'		=> 'ARIN',
	'block'		=> '213.0.0.0/20',
	'tags' 		=> 'Customer,VPN'
];

sendRequestAndPrintResponse($apiClient, $target, $action, $params);
/**
 * Smart assign a /24 from our API test block to API Test Resource
 */

echo "Smart assign a /24 from our API test block to \"API Test Resource\"\n";

$target = 'ipam';
$action = 'smartAssign';
$params = [
	'type'			=> 'ipv4',
	'rir'			=> 'ARIN',
	'mask'			=> 24,
	'code' 			=> 'api-test-block',
	'resourceQuery' => '{"name":"API Test Resource"}'
];

sendRequestAndPrintResponse($apiClient, $target, $action, $params);

/**
 * List all blocks assigned to "API Test Resource"
 */

echo "List all blocks assigned to \"API Test Resource\"\n";

$target = 'ipam';
$action = 'get';
$params = [
	'resourceQuery' => '{"name":"API Test Resource"}'
];

sendRequestAndPrintResponse($apiClient, $target, $action, $params);


/**
 * Delete aggregate 213.0.0.0/20
 */

echo "Delete aggregate 213.0.0.0/20\n";

$target = 'ipam';
$action = 'delete';
$params = [
	'block' => '213.0.0.0/20',
	'force' => 'true'
];

sendRequestAndPrintResponse($apiClient, $target, $action, $params);


/**
 * Delete resource 'API Test Resource'
 */

echo "Delete resource \"API Test Resource\"\n";

$target = 'resource';
$action = 'delete';
$params = [
	'id' => $apiTestResourceId
];

sendRequestAndPrintResponse($apiClient, $target, $action, $params);

/**
 * Send the API request and print URL, status, message and raw JSON response
 */

function sendRequestAndPrintResponse(&$apiClient, $target, $action, $params){

	$apiResponse = $apiClient->sendRequest($target, $action, $params);

	echo sprintf("%-15s%s\n", 'URL',  $apiClient->url);

	echo sprintf("%-15s%s\n", 'STATUS', ($apiResponse->status == APIClient::API_STATUS_SUCCESS ? 'SUCCESS' : 'ERROR'));

	echo sprintf("%-15s%s\n", 'MESSAGE', $apiResponse->message);

	$prettyJson = json_encode(json_decode($apiClient->rawResponse), JSON_PRETTY_PRINT);
	echo "RAW RESPONSE:\n$prettyJson\n\n";

	if( $apiResponse->httpStatus != 200 ){
		die();
	}
}


