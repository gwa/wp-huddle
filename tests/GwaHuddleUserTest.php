<?php
require_once __DIR__.'/../lib/gwa/huddle/User.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Token.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Persistance.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Api.class.php';
require_once __DIR__.'/../lib/gwa/huddle/ApiMock.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Workspace.class.php';

class GwaHuddleUserTest extends PHPUnit_Framework_TestCase
{
	private $_user;

	public static function setUpBeforeClass()
	{
		\gwa\huddle\ApiCallCached::setCacheDir(__DIR__.'/_cache/api');
	}

	public static function tearDownAfterClass()
	{
		\gwa\huddle\ApiCallCached::clearEntireCache();
	}

	protected function setUp()
	{
		$this->_user = new \gwa\huddle\User(null);
	}

	public function testUserNotAuthorized()
	{
		$this->assertFalse($this->_user->isAuthorized());
	}

	public function testUserAuthorized()
	{
		$this->_user->setAuthToken('12345');
		$this->assertTrue($this->_user->isAuthorized());
		$this->assertEquals('12345', $this->_user->getAuthToken());
	}

	public function testGetUserFromPersistance()
	{
		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');
		$api = new \gwa\huddle\Api('foo', 'bar');
		$user = \gwa\huddle\User::getUserInstance($persistance, $api);
		$this->assertTrue($user->isAuthorized());
	}

	public function testGetUserAccessTokenFromPersistance()
	{
		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');
		$persistance->set('access_token', '654321');
		$persistance->set('expires', time()-30); // is expired
		$persistance->set('refresh_token', '654321');
		$api = new \gwa\huddle\Api('foo', 'bar');
		$user = \gwa\huddle\User::getUserInstance($persistance, $api);
		$token = $user->getAccessToken();
		$this->assertInstanceOf('\gwa\huddle\Token', $token);
	}

	public function testFetchAccessToken()
	{
		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');
		$api = new \gwa\huddle\ApiMock('foo', 'bar');

		$r = new stdClass;
		$r->access_token = '1111';
		$r->expires_in = '300';
		$r->refresh_token = '2222';
		$api->setResponse($r);

		$user = \gwa\huddle\User::getUserInstance($persistance, $api);
		$token = $user->fetchAccessToken();

		$this->assertInstanceOf('\gwa\huddle\Token', $token);
		$this->assertFalse($token->isExpired());
		$this->assertEquals('1111', $token->getAccessToken());
	}

	/**
     * @expectedException        Exception
     * @expectedExceptionMessage invalid_grant
     */
	public function testFetchAccessTokenError()
	{
		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');
		$api = new \gwa\huddle\ApiMock('foo', 'bar');

		$r = new stdClass;
		$r->error = 'invalid_grant';
		$r->error_description = 'The authorisation grant was revoked';
		$r->error_uri = 'https://login.huddle.net/docs/error#TokenRequestInvalidGrant';
		$api->setResponse($r);

		$user = \gwa\huddle\User::getUserInstance($persistance, $api);
		$token = $user->fetchAccessToken();
	}

	/**
	 * The same as previous, but we use `getAccessToken()`
	 * @method testGetAccessTokenithFetch
	 */
	public function testGetAccessTokenWithFetch()
	{
		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');
		$api = new \gwa\huddle\ApiMock('foo', 'bar');

		$r = new stdClass;
		$r->access_token = '1111';
		$r->expires_in = '300';
		$r->refresh_token = '2222';
		$api->setResponse($r);

		$user = \gwa\huddle\User::getUserInstance($persistance, $api);
		$token = $user->getAccessToken();

		$this->assertInstanceOf('\gwa\huddle\Token', $token);
		$this->assertFalse($token->isExpired());
		$this->assertEquals('1111', $token->getAccessToken());
	}

	public function testFetchData()
	{
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$xml = file_get_contents(__DIR__.'/xml/User.xml');
		$api->setResponse($xml);
		\gwa\huddle\ApiCallCached::setApi($api);

		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');

		$user = \gwa\huddle\User::getUserInstance($persistance, $api);
		$data = $user->getData();

		$this->assertEquals('Ian Cooper', $user->getDisplayName());
	}

	public function testGetURI()
	{
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$xml = file_get_contents(__DIR__.'/xml/User.xml');
		$api->setResponse($xml);
		\gwa\huddle\ApiCallCached::setApi($api);

		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');

		$user = \gwa\huddle\User::getUserInstance($persistance, $api);
		$self = $user->getURI();

		$this->assertEquals('http://www.example.com', $self);
	}

	public function testGetWorkspaces()
	{
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$xml = file_get_contents(__DIR__.'/xml/User.xml');
		$api->setResponse($xml);
		\gwa\huddle\ApiCallCached::setApi($api);

		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');

		$user = \gwa\huddle\User::getUserInstance($persistance, $api);
		$ws = $user->getWorkspaces();

		$this->assertEquals(2, count($ws));
		$this->assertInstanceOf('\gwa\huddle\Workspace', $ws[0]);
	}
}
