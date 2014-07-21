<?php
require_once __DIR__.'/../lib/gwa/huddle/Persistance.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Token.class.php';

class GwaHuddlePersistanceTest extends PHPUnit_Framework_TestCase
{
	public static function setUpBeforeClass()
	{
		$filepath = __DIR__.'/_data/test.json';
		$persistance = new \gwa\huddle\Persistance($filepath);
		$persistance->purge();
	}

	public function testFileNotExist()
	{
		$filepath = __DIR__.'/_data/test.json';
		$persistance = new \gwa\huddle\Persistance($filepath);
		$this->assertInternalType('array', $persistance->getData());
	}

	public function testGet()
	{
		$filepath = __DIR__.'/_data/test.json';
		$persistance = new \gwa\huddle\Persistance($filepath);
		$persistance->set('foo', 'bar');
		$this->assertEquals('bar', $persistance->get('foo'));
		$this->assertInternalType('null', $persistance->get('baz'));
	}

	public function testPersist()
	{
		$filepath = __DIR__.'/_data/test.json';

		$persistance = new \gwa\huddle\Persistance($filepath);
		$persistance->set('foo', 'bar');
		$persistance->persist();

		$persistance2 = new \gwa\huddle\Persistance($filepath);
		$this->assertEquals('bar', $persistance2->get('foo'));
	}
}
