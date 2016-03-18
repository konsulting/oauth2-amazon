<?php

namespace Konsulting\OAuth2\Client\Provider;

use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class Amazon extends AbstractProvider
{
    const BASE_AMAZON_URL = 'https://www.amazon.com/ap/oa';

    const BASE_API_URL = 'https://api.amazon.com';
    const BASE_API_URL_TEST = 'https://api.sandbox.amazon.com';

    /**
     * Test mode indicator
     *
     * @var bool
     */
    public $testMode = false;

    protected $errorCodes = [
        1 => 'invalid_request',
        2 => 'unauthorized_client',
        3 => 'access_denied',
        4 => 'unsupported_response_type',
        5 => 'invalid_scope',
        6 => 'server_error',
        7 => 'temporarily_unavailable',
    ];

    /**
     * @param array $options
     * @param array $collaborators
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (isset($options['testMode'])) {
            $this->testMode = $options['testMode'];
        }
    }

    public function getBaseAuthorizationUrl()
    {
        return static::BASE_AMAZON_URL;
    }

    /**
     * Returns authorization parameters based on provided options.
     * Override to enable sandbox to be enabled.
     *
     * @param  array $options
     * @return array Authorization parameters
     */
    protected function getAuthorizationParameters(array $options)
    {
        $options = parent::getAuthorizationParameters($options);

        if ($this->testMode === false) {
            return $options;
        }

        return array_merge($options, ['sandbox' => 'true']);
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getBaseApiUrl() . '/auth/o2/token';
    }

    public function getDefaultScopes()
    {
        return ['profile', 'postal_code'];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getBaseApiUrl() . '/user/profile?access_token=' . $token;
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new AmazonUser($response);
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() == 302 &&
            $response->hasHeader('location') &&
            strpos($response->getHeader('location'), 'error=') !== false
        ) {
            $url = $response->getHeader('location');

            $uri = parse_url($url);
            parse_str($uri['query'], $query);

            $message = $query['error'] . (isset($query['error_description']) ? ': ' . $query['error_description'] : '');
            $errorCode = array_search($query['error'], $this->errorCodes);

            throw new IdentityProviderException($message, $errorCode, $query);
        }
    }

    /**
     * Get the base Amazon URL.
     *
     * @return string
     */
    private function getBaseApiUrl()
    {
        return $this->testMode ? static::BASE_API_URL_TEST : static::BASE_API_URL;
    }
}
