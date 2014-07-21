<?php
require_once __DIR__.'/../lib/gwa/huddle/RequestResponseHandler.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Token.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Persistance.class.php';

class GwaHuddleRequestResponseHandlerTest extends PHPUnit_Framework_TestCase
{
	static private $_persistance;

	public static function setUpBeforeClass()
	{
		self::$_persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/test.json');
	}

	public function testSuccessfulResponse()
	{
		$data = array('code'=>'abcd1234');
		$handler = new \gwa\huddle\RequestResponseHandler($data, self::$_persistance);
		$this->assertTrue($handler->isSuccessful());
	}

	public function testUnsuccessfulResponse()
	{
		$data = array('error'=>'access_denied');
		$handler = new \gwa\huddle\RequestResponseHandler($data, self::$_persistance);
		$this->assertFalse($handler->isSuccessful());
		$this->assertEquals($handler->getError(), $data['error']);
	}

	public function testPersistance()
	{
		$data = array('code'=>'abcd1234');
		$handler = new \gwa\huddle\RequestResponseHandler($data, self::$_persistance);
		$this->assertTrue($handler->isSuccessful());
		$handler->persist();
		$this->assertEquals(self::$_persistance->get('auth_token'), $data['code']);
		self::$_persistance->purge();
	}

	public function testhandle()
	{
		$data = array('code'=>'abcd1234');
		$handler = new \gwa\huddle\RequestResponseHandler($data, self::$_persistance);
		$this->assertTrue($handler->handle());
		$this->assertEquals(self::$_persistance->get('auth_token'), $data['code']);
		self::$_persistance->purge();
	}
}
