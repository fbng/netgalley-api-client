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
     * @var string
     */
    const NETGALLEY_LIVE_DOMAIN = 'https://api.netgalley.com';

    /**
     * @var string
     */
    const NETGALLEY_TEST_DOMAIN = 'https://api.stage02.netgalley.com';

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiSecret;

    /**
     * @var string
     */
    protected $apiUser;

    /**
     * @var array
     */
    protected $hashData;

    /**
     * @var bool
     */
    protected $isTest = false;

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
     * Get the Authorization header.
     *
     * @param string $path The API path to request from.
     * @param string $method The request method.
     * @param array $data The data to submit.
     *
     * @return string
     */
    protected function getAuthorizationHeader($path, $method = 'GET', $data = array())
    {
        // compile the hash data
        $hashData = is_array($data) ? $data : array();

        $hashData = array_merge($hashData, array(
            'apikey' => $this->apiKey,
            'method' => $method,
            'url' => $this->getRequestUrl($path),
        ));

        ksort($hashData);

        return 'Authorization: user=' . $this->apiUser . ',apikey=' . $this->apiKey
            . ',hash=' . hash_hmac('sha256', json_encode($hashData), $this->apiSecret);
    }

    /**
     * Get the host to interact with.
     *
     * @return string
     */
    protected function getHost()
    {
        return $this->isTest ? self::NETGALLEY_TEST_DOMAIN : self::NETGALLEY_LIVE_DOMAIN;
    }

    /**
     * Get the request URL.
     *
     * @param string $path The API path to request from.
     * @param string $method The request method.
     * @param array $data The data to submit.
     *
     * @return string
     */
    protected function getRequestUrl($path, $method = 'GET', $data = array())
    {
        $query = '';
        if ($method === 'GET' && $data) {
            $query = '?' . http_build_query($data);
        }

        return $this->getHost() . '/' . trim($path, '/') . $query;
    }

    /**
     * Get an array of reserved data keys.
     *
     * @return array
     */
    protected function getReservedKeys()
    {
        return array('apikey', 'method', 'url');
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

        $data = (!is_array($data) ? (array)$data : $data);

        $reservedKeys = $this->getReservedKeys();
        foreach ($data as $key => $value) {
            if (in_array($key, $reservedKeys)) {
                throw new \UnexpectedValueException('Data uses a reserved key: "' . $key . '".');
            }
        }

        // set the request
        curl_setopt($curl, CURLOPT_URL, $this->getRequestUrl($path, $method, $data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($this->isTest) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $headers = array();

        // set the method of the request and process any data
        if ($method !== 'GET') {

            if (is_array($data) && $data) {

                $jsonData = json_encode($data);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Content-Length: ' . strlen($jsonData);
            }
            else {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        else {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        // set the headers
        if ($authorizationHeader = $this->getAuthorizationHeader($path, $method, $data)) {
            $headers[] = $authorizationHeader;
        }
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
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
