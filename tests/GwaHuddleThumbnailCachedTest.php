<?php
require_once __DIR__.'/../lib/gwa/huddle/ThumbnailCached.class.php';

class GwaHuddleThumbnailCachedTest extends PHPUnit_Framework_TestCase
{
	private static $_api;
	private static $_document;
	private static $_thumbnail;

	public static function setUpBeforeClass()
	{
		self::$_api = new \gwa\huddle\ApiMock('foo', 'bar');
		\gwa\huddle\ThumbnailCached::setApi(self::$_api);
		\gwa\huddle\ThumbnailCached::setCacheDir(__DIR__.'/_cache/thumbs');

		$xml = simplexml_load_file(__DIR__.'/xml/Document.xml');
		self::$_document = new \gwa\huddle\Document($xml);
	}

	public static function tearDownAfterClass()
	{
		\gwa\huddle\ThumbnailCached::clearEntireCache();
	}

	public function testGetThumbnailInstance()
	{
		$thumbnail = \gwa\huddle\ThumbnailCached::getThumbnailInstance(self::$_document);
		$this->assertInstanceOf('\gwa\huddle\ThumbnailCached', $thumbnail);
	}

	public function testGetHasThumbnail()
	{
		$thumbnail = \gwa\huddle\ThumbnailCached::getThumbnailInstance(self::$_document);
		$this->assertTrue($thumbnail->hasThumbnail());
	}

	public function testNotCached()
	{
		$thumbnail = \gwa\huddle\ThumbnailCached::getThumbnailInstance(self::$_document);
		$this->assertFalse($thumbnail->isCached());
	}

	public function testGetKey()
	{
		$thumbnail = \gwa\huddle\ThumbnailCached::getThumbnailInstance(self::$_document);
		$pattern = '/^[a-z0-9]{32}$/';
		$this->assertRegExp($pattern, $thumbnail->getKey());
	}

	public function testFilePath()
	{
		$thumbnail = \gwa\huddle\ThumbnailCached::getThumbnailInstance(self::$_document);
		$pattern = '/[a-z0-9]{32}\-[0-9]+\.jpg+$/';
		$this->assertRegExp($pattern, $thumbnail->getFilePath());
	}

	public function testCachedVersionNumber()
	{
		$thumbnail = \gwa\huddle\ThumbnailCached::getThumbnailInstance(self::$_document);
		$fp = $thumbnail->getFilePath();
		$this->assertEquals($thumbnail->getCacheVersion($fp), self::$_document->getVersion());
	}

	/**
     * @expectedException        Exception
     * @expectedExceptionMessage invalid filepath
     */
	public function testCachedVersionNumberInvalid()
	{
		$thumbnail = \gwa\huddle\ThumbnailCached::getThumbnailInstance(self::$_document);
		$fp = 'invalid-string';
		$thumbnail->getCacheVersion($fp);
	}

	public function testGetContent()
	{
		$content = file_get_contents(__DIR__.'/img/thumb.jpg');
		self::$_api->setRawData($content);
		$thumbnail = \gwa\huddle\ThumbnailCached::getThumbnailInstance(self::$_document);
		$this->assertEquals($content, $thumbnail->getContent());
	}

	public function testIsCached()
	{
		$content = file_get_contents(__DIR__.'/img/thumb.jpg');
		$thumbnail = \gwa\huddle\ThumbnailCached::getThumbnailInstance(self::$_document);
		$this->assertTrue($thumbnail->isCached());
		$this->assertEquals($content, $thumbnail->getContent());
	}
}
