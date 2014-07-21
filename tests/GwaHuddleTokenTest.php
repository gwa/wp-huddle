<?php
require_once __DIR__.'/../lib/gwa/huddle/Token.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Persistance.class.php';

class GwaHuddleTokenTest extends PHPUnit_Framework_TestCase
{
	private $_validtoken;
	private $_invalidtoken;
	private $_accesstoken = 'S1AV32hkKG';
	private $_refreshtoken = '8xLOxBtZp8';
	private $_expiresfuture;
	private $_expirespast;

	public function setUp()
	{
		$this->_expiresfuture = time() + 300;
		$this->_expirespast = time() - 300;
		$this->_validtoken = new \gwa\huddle\Token(
			$this->_accesstoken,
			$this->_expiresfuture,
			$this->_refreshtoken
		);
		$this->_invalidtoken = new \gwa\huddle\Token(
			$this->_accesstoken,
			$this->_expirespast,
			$this->_refreshtoken
		);
	}

	public function testSetUser()
	{
		$token = new \gwa\huddle\Token(
			$this->_accesstoken,
			$this->_expiresfuture,
			$this->_refreshtoken
		);
		$this->assertNull($token->getUser());
		$user = new \gwa\huddle\User();
		$token->setUser($user);
		$this->assertEquals($user, $token->getUser());
	}

	public function testUpdate()
	{
		$token = new \gwa\huddle\Token(
			$this->_accesstoken,
			$this->_expiresfuture,
			$this->_refreshtoken
		);
		$this->assertEquals($token->getAccessToken(), $this->_accesstoken);
		$this->assertEquals($token->getExpiresIn(), $this->_expiresfuture - time());
		$this->assertEquals($token->getRefreshToken(), $this->_refreshtoken);

		$token->update('abcd1234', time() + 600, 'foobar');
		$this->assertEquals($token->getAccessToken(), 'abcd1234');
		$this->assertEquals($token->getExpiresIn(), 600);
		$this->assertEquals($token->getRefreshToken(), 'foobar');
	}

	public function testGetAccessToken()
	{
		$this->assertEquals($this->_accesstoken, $this->_validtoken->getAccessToken());
	}

	public function testGetRefreshToken()
	{
		$this->assertEquals($this->_refreshtoken, $this->_validtoken->getRefreshToken());
	}

	public function testIsNotExpired()
	{
		$this->assertFalse($this->_validtoken->isExpired());
	}

	public function testIsExpired()
	{
		$this->assertTrue($this->_invalidtoken->isExpired());
	}

	/**
     * @expectedException        Exception
     * @expectedExceptionMessage expired
     */
	public function testInvalidAccessTokenException()
	{
		$this->_invalidtoken->getAccessToken();
	}

	public function testInvalidGrantException()
	{
		$api = new \gwa\huddle\ApiMock('foo', 'bar');

		// 1. api returns invalid grant
		$r = new stdClass;
		$r->error = 'invalid_grant';
		$api->setResponse($r);

		// 2. api returns new token data
		$r = new stdClass;
		$r->access_token = 'adcb1234';
		$r->expires_in = time() + 300;
		$r->refresh_token = 'foobar';
		$api->setResponse($r);

		// seemingly valid token
		$validtoken = new \gwa\huddle\Token(
			$this->_accesstoken,
			time() + 300,
			$this->_refreshtoken,
			null,
			$api,
			new \gwa\huddle\User(null)
		);
		$this->assertFalse($validtoken->isExpired());
		$this->assertEquals($validtoken->getAccessToken(), $this->_accesstoken);
		$validtoken->refresh();
		$this->assertEquals($validtoken->getAccessToken(), 'adcb1234');
	}

	public function testGetFromPersistance()
	{
		$user = new \gwa\huddle\User(null);

		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');
		$api = new \gwa\huddle\Api('foo', 'bar');
		$token = \gwa\huddle\Token::getTokenInstance($persistance, $api, $user);
		$this->assertNull($token);

		$persistance->set('access_token', '654321');
		$persistance->set('expires', time()-30); // is expired
		$persistance->set('refresh_token', '654321');
		$token = \gwa\huddle\Token::getTokenInstance($persistance, $api, $user);
		$this->assertInstanceOf('\gwa\huddle\Token', $token);
	}

	public function testRefresh()
	{
		$user = new \gwa\huddle\User(null);

		// save expired token in persistance
		$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
		$persistance->set('auth_token', '123456');
		$persistance->set('access_token', '654321');
		$persistance->set('expires', time()-30); // is expired
		$persistance->set('refresh_token', '654321');

		// create mock api with response data
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$r = new stdClass;
		$r->access_token = '1111';
		$r->expires_in = '300';
		$r->refresh_token = '2222';
		$api->setResponse($r);

		// get expired token from persistance
		$token = \gwa\huddle\Token::getTokenInstance($persistance, $api, $user );
		$this->assertEquals('654321', $token->getRefreshToken());
		$this->assertTrue($token->isExpired());

		// refresh using api data
		$token->refresh();
		$this->assertEquals('1111', $token->getAccessToken());
		$this->assertEquals('2222', $token->getRefreshToken());
		$this->assertFalse($token->isExpired());
	}
}
