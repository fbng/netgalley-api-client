<?php

// optional: if you do not have a PSR-0 compliant autoloader,
// you will need to include the class file manually
require_once(__DIR__ . '/lib/NetGalley/API/Client.php');

use NetGalley\API\Client;

// obtain your credentials from NetGalley
$myUser = 'example';
$myApiKey = 'KEY';
$myApiSecret = 'SECRET';

$myUser = 'admin';
$myApiKey = 'YJ2E3XZ6PFXTTU6PTRWN';
$myApiSecret = 'qxa4jNFRpXWPwBNXkhk3BChdq4e3Nw8kmVsP6Sj3T9';

// if set to true, the client will target a staging server
$testMode = true;

// instantiate the client using your credentials
$client = new Client($myUser, $myApiKey, $myApiSecret, $testMode);

// make an API request; see the relevant documentation
// for details of the API you are requesting
$response = $client->makeRequest('/widgets', 'POST', array(
    'email' => 'customer@gmail.com',
    'isbn' => '1234567890123',
    'market' => 'US',
    'temporaryDrm' => false,
    'firstName' => 'Some',
    'lastName' => 'Customer'
));

// the response will be a JSON-encoded string, so decode it
$response = json_decode($response, true);

// do something with the response
echo 'Response was: ' . print_r($response, true) . PHP_EOL;
