<?php

namespace Konsulting\OAuth2\Client\Provider;

use Mockery as m;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class AmazonTest extends \PHPUnit_Framework_TestCase
{
    protected $mockOptions = [
        'clientId' => 'mock_client_id',
        'clientSecret' => 'mock_secret',
        'redirectUri' => 'none',
    ];
    protected $provider;

    public function setUp()
    {
        parent::setUp();
        $this->provider = $this->createProvider($this->mockOptions);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function AuthorizationUrlIsCorrectlyGenerated()
    {
        $url = $this->provider->getAuthorizationUrl();

        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    /**
     * @test
     */
    public function TestModeCanBeEnabled()
    {
        $this->assertNotRegExp('/sandbox/', $this->provider->getAuthorizationUrl());

        $testProvider = $this->createTestProvider($this->mockOptions);
        $this->assertRegExp('/sandbox/', $testProvider->getAuthorizationUrl());
    }

    /**
     * @test
     */
    public function CanGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getStatusCode')
            ->times(1)
            ->andReturn(200);
        $response->shouldReceive('getHeader')
            ->times(1)
            ->andReturn('application/json');
        $response->shouldReceive('getBody')
            ->times(1)
            ->andReturn(json_encode([
                "access_token" => "mock_access_token",
                "token_type" => "bearer",
                "expires_in" => 3600,
                "refresh_token" => "mock_refresh_token",
            ]));
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());

        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId(), 'Amazon does not provide the user ID with the access token. Expected null.');
    }

    /**
     * @test
     */
    public function checkUserData()
    {
        $provider = new TestingAmazonProvider();

        $token = m::mock('League\OAuth2\Client\Token\AccessToken');
        $user = $provider->getResourceOwner($token);

        $this->assertEquals(4, $user->getId($token));
        $this->assertEquals('mock_name', $user->getName($token));
        $this->assertEquals('mock_email', $user->getEmail($token));
        $this->assertEquals('mock_postcode', $user->getPostcode($token));
    }

    /**
     * @test
     */
    public function checkDefaultScopes()
    {
        $this->assertEquals(['profile', 'postal_code'], $this->provider->getDefaultScopes());
    }

    /**
     * @test
     */
    public function itProperlyHandlesErrorResponses()
    {
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getStatusCode')
            ->times(1)
            ->andReturn(302);
        $postResponse->shouldReceive('hasHeader')
            ->times(1)
            ->andReturn(true);
        $postResponse->shouldReceive('getHeader')
            ->times(3)
            ->andReturn('location: https://www.example.com?error=invalid_request&error_description=mock_error_description');
        $postResponse->shouldReceive('getBody')
            ->times(1)
            ->andReturn('');
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($postResponse);
        $this->provider->setHttpClient($client);
        $errorMessage = '';
        $errorCode = 0;
        try {
            $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        } catch (IdentityProviderException $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();
        }
        $this->assertEquals('invalid_request: mock_error_description', $errorMessage);
        $this->assertEquals(1, $errorCode);
    }

    protected function createProvider($options = [])
    {
        return new Amazon($options);
    }

    protected function createTestProvider($options = [])
    {
        $options = array_merge(['testMode' => true], $options);

        return $this->createProvider($options);
    }
}

class TestingAmazonProvider extends Amazon
{
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        return json_decode('{"user_id": 4, "name": "mock_name", "username": "mock_username", "email": "mock_email", "postcode": "mock_postcode"}', true);
    }
}
