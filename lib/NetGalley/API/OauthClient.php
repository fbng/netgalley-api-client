<?php

namespace NetGalley\API;

/**
 * Client for connecting to the public NetGalley API using OAuth2.
 * See {@link https://github.com/fbng/netgalley-api-client}
 * for usage instructions.
 *
 * To obtain API credentials or for any other support, please contact
 * {@link concierge@netgalley.com}.
 */
class OauthClient extends Client
{
    /**
     * @var string
     */
    const NETGALLEY_TOKEN_PATH = 'oauth/token';

    /**
     * @var string
     */
    protected $authorizationCode;

    /**
     * @var string
     */
    protected $clientToken;

    /**
     * @var string
     */
    protected $refreshToken;

    /**
     * @var integer
     */
    protected $tokenExpires;

    /**
     * Construct an instance of the OAuth2 API client.
     *
     * @param string $clientId The OAuth Client ID provided by NetGalley.
     * @param string $clientSecret The OAuth Client secret.
     * @param string $authorizationCode The authorization code if requesting a token for granting third-party access.
     * @param bool $isTest Set to true during development.
     */
    public function __construct($clientId, $clientSecret, $authorizationCode = null, $isTest = false)
    {
        parent::__construct(null, $clientId, $clientSecret, $isTest);

        $this->authorizationCode = $authorizationCode;

        // make an initial request to get the OAuth token
        $this->requestToken();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationHeader($path, $method = 'GET', $data = array())
    {
        // authorization header is only needed if a token is being requested or refreshed
        if ($path !== self::NETGALLEY_TOKEN_PATH) {
            return 'Authorization: Bearer ' . $this->clientToken;
        }

        return 'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestUrl($path, $method = 'GET', $data = array())
    {
        $query = '';

        if ($path === self::NETGALLEY_TOKEN_PATH) {
            if (!$this->refreshToken) {
                if ($this->authorizationCode) {
                    $query = '?grant_type=authorization_code' // OAuth2::GRANT_TYPE_AUTH_CODE
                        . '&code=' . $this->authorizationCode
                    ;
                }
                else {
                    $query = '?grant_type=client_credentials'; // OAuth2::GRANT_TYPE_CLIENT_CREDENTIALS
                }
            }
            else {
                $query = '?grant_type=refresh_token' // OAuth2::GRANT_TYPE_REFRESH_TOKEN
                    . '&refresh_token=' . $this->refreshToken
                ;
            }
        }

        if ($method === 'GET' && $data) {
            $query = ($query ? '&' : '?') . http_build_query($data);
        }

        return $this->getHost() . '/' . trim($path, '/') . $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function getReservedKeys()
    {
        // from OAuth2\OAuth2::grantAccessToken()
        return array('grant_type', 'scope', 'code', 'redirect_url', 'username', 'password', 'refresh_token');
    }

    /**
     * Get the current refresh token.
     *
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Get the current token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->clientToken;
    }

    /**
     * Get the time in seconds until the current token expires.
     *
     * @return integer
     */
    public function getTokenLifetime()
    {
        $expiresIn = $this->tokenExpires - time();

        return $expiresIn > 0 ? $expiresIn : 0;
    }

    /**
     * Submit a token request.
     */
    public function requestToken()
    {
        // send the request
        $token = json_decode($this->makeRequest(self::NETGALLEY_TOKEN_PATH));

        // capture the returned data
        $this->clientToken = empty($token->access_token) ? '' : $token->access_token;
        $this->refreshToken = empty($token->refresh_token) ? '' : $token->refresh_token;
        $this->tokenExpires = empty($token->expires_in) ? time() : time() + $token->expires_in;
    }
}
