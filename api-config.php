<?php
namespace api;
$config = [
	//URL for API requests to be sent to.
	//Update this with your instance ID (hosted version) or correct path (local version).
	'instanceUrl' => 'https://cloud.6connect.com/6c_<INSTANCE ID>',

	//Your public API key.
	//Update this to the public API key for your user located at Admin->API in the web interface.
	'apiKey' => '',

	//Your secret API key (will not be sent with the request, but is used to create the request).
	//Update this with the Secret Key for the user located at Admin->API in the web interface.
	'apiSecretKey' => '',
];
