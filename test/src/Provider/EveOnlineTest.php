<?php

namespace Killmails\OAuth2\Client\Test\Provider;

use Killmails\OAuth2\Client\Provider\EveOnline;
use League\OAuth2\Client\Token\AccessToken;
use Mockery as m;

class EveOnlineTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected function setUp()
    {
        $this->provider = new EveOnline([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
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

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetResourceOwnerDetailsUrl()
    {
        $token = new AccessToken([
            'access_token' => 'mock_access_token'
        ]);

        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/verify', $uri['path']);
    }

    public function testScopes()
    {
        $options = [
            'scope' => [
                uniqid(),
                uniqid()
            ]
        ];

        $url = $this->provider->getAuthorizationUrl($options);

        $this->assertContains(urlencode(implode(',', $options['scope'])), $url);
    }

    public function testGetAccessToken()
    {
        $refreshToken = uniqid();
        $expiresIn = rand(600, 1200);

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(json_encode([
            'access_token' => 'mock_access_token',
            'token_type' => 'Bearer',
            'refresh_token' => $refreshToken,
            'expires_in' => $expiresIn,
        ]));
        $response->shouldReceive('getHeader')->andReturn([
            'content-type' => 'json'
        ]);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code'
        ]);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals(time() + $expiresIn, $token->getExpires());
        $this->assertEquals($refreshToken, $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testCreateResourceOwner()
    {
        $characterId = rand(100, 999);
        $characterName = uniqid();
        $characterOwnerHash = uniqid();

        $tokenResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $tokenResponse->shouldReceive('getBody')->andReturn(http_build_query([
            'access_token' => 'mock_access_token',
            'token_type' => 'Bearer',
            'expires_in' => 1000,
            'refresh_token' => 'mock_refresh_token'
        ]));
        $tokenResponse->shouldReceive('getHeader')->andReturn([
            'content-type' => 'application/x-www-form-urlencoded'
        ]);
        $tokenResponse->shouldReceive('getStatusCode')->andReturn(200);

        $ownerResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $ownerResponse->shouldReceive('getBody')->andReturn(json_encode([
            'CharacterID' => $characterId,
            'CharacterName' => $characterName,
            'CharacterOwnerHash' => $characterOwnerHash,
        ]));
        $ownerResponse->shouldReceive('getHeader')->andReturn([
            'content-type' => 'json'
        ]);
        $ownerResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($tokenResponse, $ownerResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $owner = $this->provider->getResourceOwner($token);
        $this->assertEquals($characterId, $owner->getCharacterID());
        $this->assertEquals($characterId, $owner->toArray()['CharacterID']);
        $this->assertEquals($characterName, $owner->getCharacterName());
        $this->assertEquals($characterName, $owner->toArray()['CharacterName']);
        $this->assertEquals($characterOwnerHash, $owner->getCharacterOwnerHash());
        $this->assertEquals($characterOwnerHash, $owner->toArray()['CharacterOwnerHash']);
    }

    /**
     * @expectedException League\OAuth2\Client\Provider\Exception\IdentityProviderException
     **/
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $error = uniqid();
        $description = uniqid();
        $status = rand(400, 600);

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(json_encode([
            'error' => $error,
            'error_description' => $description
        ]));
        $response->shouldReceive('getHeader')->andReturn([
            'content-type' => 'json'
        ]);
        $response->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', [
           'code' => 'mock_authorization_code'
        ]);

    }
}
