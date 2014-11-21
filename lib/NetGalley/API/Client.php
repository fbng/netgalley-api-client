<?php

namespace NetGalley\API;

/**
 * Client for connecting to the public NetGalley API.
 * See {@link https://github.com/fbng/netgalley-api-client}
 * for usage instructions.
 *
 * To obtain API credentials or for any other support, please contact
 * {@link concierge@netgalley.com}.
 */
class Client
{
    /**
     * @const string
     */
    const NETGALLEY_LIVE_DOMAIN = 'https://api.netgalley.com';

    /**
     * @const string
     */
    const NETGALLEY_TEST_DOMAIN = 'https://api.stage02.netgalley.com';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiSecret;

    /**
     * @var string
     */
    private $apiUser;

    /**
     * @var array
     */
    private $hashData;

    /**
     * @var bool
     */
    private $isTest = false;

    /**
     * Construct an instance of the API client.
     *
     * @param string $apiUser The login username at netgalley.com for the account that has API credentials.
     * @param string $apiKey The public API key that gets sent with each request.
     * @param string $apiSecret The secret that is used to create the API hash.
     * @param bool $isTest Set to true during development.
     */
    public function __construct($apiUser, $apiKey, $apiSecret, $isTest = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->apiUser = $apiUser;
        $this->isTest = $isTest;
    }

    /**
     * Get the host to interact with.
     *
     * @return string
     */
    private function getHost()
    {
        return $this->isTest ? self::NETGALLEY_TEST_DOMAIN : self::NETGALLEY_LIVE_DOMAIN;
    }

    /**
     * Make a request to the remote API endpoint via cURL.
     *
     * @param string $path The API path to request from.
     * @param string $method The request method.
     * @param array $data The data to submit.
     *
     * @return string
     */
    public function makeRequest($path, $method = 'GET', $data = array())
    {
        $curl = curl_init();

        $path = '/' . trim($path, '/');

        $data = (!is_array($data) ? (array)$data : $data);

        foreach ($data as $key => $value) {
            if (in_array($key, array('apikey', 'method', 'url'))) {
                throw new \UnexpectedValueException('Data uses a reserved key: ' . $key . '".');
            }
        }

        // compile the hash data
        $hashData = is_array($data) ? $data : array();

        $hashData = array_merge($hashData, array(
            'apikey' => $this->apiKey,
            'method' => $method,
            'url' => $this->getHost() . $path,
        ));

        ksort($hashData);

        $requestData = array(
            'user=' . $this->apiUser,
            'apikey=' . $this->apiKey,
            'hash=' . hash_hmac('sha256', json_encode($hashData), $this->apiSecret)
        );

        // set the request
        $request = $this->getHost() . $path . '?' . implode('&', $requestData);

        curl_setopt($curl, CURLOPT_URL, $request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($this->isTest) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        // set the method of the request and process any data
        if ($method !== 'GET') {

            if (is_array($data) && $data) {

                $data = json_encode($data);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data))
                );
            }

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }

        // execute the request
        $returnData = curl_exec($curl);

        if (!$returnData) {
            $returnData = json_encode(array('http_code' => curl_getinfo($curl, CURLINFO_HTTP_CODE)));
        }

        curl_close($curl);

        return $returnData;
    }
}
