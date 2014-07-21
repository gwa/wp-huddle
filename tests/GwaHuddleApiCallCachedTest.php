<?php
require_once __DIR__.'/../lib/gwa/huddle/Api.class.php';
require_once __DIR__.'/../lib/gwa/huddle/ApiMock.class.php';
require_once __DIR__.'/../lib/gwa/huddle/ApiCallCached.class.php';

class GwaHuddleApiCallCachedTest extends PHPUnit_Framework_TestCase
{
	private static $_cachedir;

	public static function setUpBeforeClass()
	{
		self::$_cachedir = __DIR__.'/_cache/api';
	}

	public static function tearDownAfterClass()
	{
		$api = new \gwa\huddle\Api('foo', 'bar');
		$call = new \gwa\huddle\ApiCallCached($api, self::$_cachedir, 'entry/', array('foo'=>'bar'), 30);
		$call->clearCache();
	}

	public function testFactoryNotInit()
	{
		$this->setExpectedException('Exception');
		\gwa\huddle\ApiCallCached::create('entry/', array('foo'=>'bar'), 30);
	}

	public function testFactoryInit()
	{
		\gwa\huddle\ApiCallCached::setApi(new \gwa\huddle\Api('foo', 'bar'));
		\gwa\huddle\ApiCallCached::setCacheDir(self::$_cachedir);
		$call = \gwa\huddle\ApiCallCached::create('entry/', array('foo'=>'bar'), 30);
		$this->assertInstanceOf('\gwa\huddle\ApiCallCached', $call);
	}

	public function testGetKey()
	{
		$api = new \gwa\huddle\Api('foo', 'bar');
		$call1 = new \gwa\huddle\ApiCallCached($api, self::$_cachedir, 'entry/', array('foo'=>'bar'), 30);
		$pattern = '/^[a-z0-9]{32}$/';

		$this->assertRegExp($pattern, $call1->getKey());

		$call2 = new \gwa\huddle\ApiCallCached($api, self::$_cachedir, 'entry2/', array('foo'=>'bar'), 30);

		$this->assertRegExp($pattern, $call2->getKey());
		$this->assertFalse($call1->getKey() === $call2->getKey());
	}

	public function testIsNotCached()
	{
		$api = new \gwa\huddle\Api('foo', 'bar');
		$call = new \gwa\huddle\ApiCallCached($api, self::$_cachedir, 'entry/', array('foo'=>'bar'), 30);
		$this->assertFalse($call->isCached());
	}

	public function testResponse()
	{
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$r = simplexml_load_file(__DIR__.'/xml/User.xml');
		$api->setResponse($r);
		$call = new \gwa\huddle\ApiCallCached($api, self::$_cachedir, 'entry/', array('foo'=>'bar'), 30);

		$this->assertFalse($call->isCached());
		$response = $call->call();
		$this->assertEquals($r->foo, $response->foo);
		$this->assertTrue($call->isCached());
	}

	public function testInvalidCache()
	{
		$api = new \gwa\huddle\Api('foo', 'bar');
		$call = new \gwa\huddle\ApiCallCached($api, self::$_cachedir, 'entry/', array('foo'=>'bar'), 30);
		// should be cached from previous test
		// change cache timestamp
		$call->touch(time()-(31*60));
		$this->assertFalse($call->isCached());
	}
}
