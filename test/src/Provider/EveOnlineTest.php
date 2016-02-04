<?php

namespace Killmails\OAuth2\Client\Test\Provider;

use Mockery as m;

class EveOnlineTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected function setUp()
    {
        $this->provider = new \Killmails\OAuth2\Client\Provider\EveOnline([
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


    public function testScopes()
    {
        $options = ['scope' => [uniqid(), uniqid()]];

        $url = $this->provider->getAuthorizationUrl($options);

        $this->assertContains(urlencode(implode(',', $options['scope'])), $url);
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
}
