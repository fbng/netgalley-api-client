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

If you are connecting to the NetGalley API using OAuth2 (more about this below), also add the `require_once` line:

```php
require_once('/path/to/lib/NetGalley/API/OauthClient.php');
```

# Usage

To use the client library, first obtain a set of API credentials from NetGalley (contact your concierge representative if you have any questions).  These credentials will either be a Key / Secret pair or an OAuth Client ID / Secret pair, which will determine which method below you will use to access the NetGalley API.

## Key / Secret connection method

If you have received Key / Secret credentials, use this example code as a basis for accessing the API:

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
$response = $client->makeRequest('/private/widgets', 'POST', array(
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

## OAuth2 Client ID / Secret connection method

If you have received OAuth2 credentials, use this example code as a basis for accessing the API:

```php
use NetGalley\API\OauthClient;

// obtain your credentials from NetGalley
$clientId = 'CLIENTID';
$clientSecret = 'SECRET';

// if requesting a token to grant third-party access, set the authorization
// code captured by the redirect URI
//
// NOTE: the NetGalley API does not currently support authorizing third-party
// access - this is included only to avoid conflicts in client implementations
// in the future if it ever becomes a supported feature
$authorizationCode = '';

// if set to true, the client will target a staging server
$testMode = true;

// instantiate the client using your credentials
$client = new OauthClient($clientId, $clientSecret, $authorizationCode, $testMode);

// request an access token once (will be set internally for subsequent requests)
$client->requestToken();

// make requests and handle responses the same as above
$response = null;
```

## Handling Large Data Results

If you are granted access to any reporting APIs, you may foresee instances in your application where large data sets may be coming from the API.  You can avoid loading the entire data set into memory by streaming the results directly into a temporary file.

In the example below, `$responseFile` is a file handle resource to a temporary file containing the response data from the API request.  You can then write the file contents to the output buffer to provide a CSV download, for example, or rename the file to a permanent location.

```php
$responseFile = $client->makeFileRequest('/private/reports/example_report', 'GET', array('filter' => 'example'));

if ($responseFile !== false) {

    // Do something with $responseFile

    fclose($responseFile);
}
```

# API Documentation

See the [API Documentation](http://htmlpreview.github.com/?https://github.com/fbng/netgalley-api-client/blob/master/documentation/index.html) within this repository for up-to-date information on each REST API.
