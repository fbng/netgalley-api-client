NetGalley Public REST API Client
================================

The NetGalley Public REST API provides a way for NetGalley's partners to interact with the system. The library currently supports only PHP, but the API itself can of course be accessed via REST by any system. 

# Installation

To install with [Composer](https://getcomposer.org/), add the following to your `composer.json` file:

```
    "require": {
        ...
        "fbng/netgalley-api-client": "*",
        ...
    },
```

Then run a `composer update` and you should be all set.

To install without Composer, download the latest archive, extract it to your project, and add the appropriate `require_once` line to your code:

```php
require_once('/path/to/lib/NetGalley/API/Client.php');
```

# Usage

To use the client library, first obtain a set of API credentials from NetGalley (contact your concierge representative if you have any questions). Once you have credentials, use this example code as a basis for accessing the API:

```php
use NetGalley\API\Client;

// obtain your credentials from NetGalley
$myUser = 'example';
$myApiKey = 'KEY';
$myApiSecret = 'SECRET';

// if set to true, the client will target a staging server
$testMode = true;

// instantiate the client using your credentials
$client = new Client($myUser, $myApiKey, $myApiSecret, $testMode);

// make an API request; see the relevant documentation
// for details of the API you are requesting; here we
// are creating a new widget for a customer
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
```

That's it!

# API Documentation

See the [API Documentation](https://github.com/fbng/netgalley-api-client/blob/master/documentation/index.html) within this repository for up-to-date information on each REST API.
