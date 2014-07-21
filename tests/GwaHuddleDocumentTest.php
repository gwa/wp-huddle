<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../lib/gwa/huddle/ApiCallCached.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Document.class.php';

class GwaHuddleDocumentTest extends PHPUnit_Framework_TestCase
{
	private static $_document;

	public static function setUpBeforeClass()
	{
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$xml = file_get_contents(__DIR__.'/xml/Document.xml');
		$api->setResponse($xml);
		\gwa\huddle\ApiCallCached::setApi($api);
		\gwa\huddle\ApiCallCached::setCacheDir(__DIR__.'/_cache/api');
		self::$_document = \gwa\huddle\Document::getDocumentInstance('http://foo.com');
	}

	public static function tearDownAfterClass()
	{
		\gwa\huddle\ApiCallCached::clearEntireCache();
	}

	public function testGetDocument()
	{
		$this->assertInstanceOf('\gwa\huddle\Document', self::$_document);
	}

	public function testGetDisplayName()
	{
		$this->assertEquals('TPS report May 2010', self::$_document->getDisplayName());
	}

	public function testDescription()
	{
		$this->assertEquals('relentlessly mundane and enervating.', self::$_document->getDescription());
	}

	public function testGetURI()
	{
		$this->assertEquals('files/documents/8655808', self::$_document->getURI());
	}

	public function testGetParentURI()
	{
		$this->assertEquals('files/folders/12345', self::$_document->getParentURI());
	}

	public function testGetId()
	{
		$this->assertEquals('8655808', self::$_document->getId());
	}

	public function testGetFolderId()
	{
		$this->assertEquals('12345', self::$_document->getFolderId());
	}

	public function testGetMimeType()
	{
		$this->assertEquals('application/excel', self::$_document->getMimeType());
	}

	public function testGetMimeTypeClassName()
	{
		$this->assertEquals('excel', self::$_document->getMimeTypeClassName());
	}

	public function testGetFileName()
	{
		$this->assertEquals('foobar.xls', self::$_document->getFileName());
	}

	public function testGetFileURI()
	{
		$this->assertEquals('https://api.huddle.net/files/documents/8655808/content', self::$_document->getFileURI());
	}

	public function testHasThumbnail()
	{
		$this->assertTrue(self::$_document->hasThumbnail());
	}

	public function testHasNoThumbnail()
	{
		$xml = simplexml_load_file(__DIR__.'/xml/DocumentNoThumbnail.xml');
		$document = new \gwa\huddle\Document($xml);
		$this->assertFalse($document->hasThumbnail());
	}

	public function testGetThumbnailURI()
	{
		$this->assertEquals('https://api.huddle.net/files/documents/versions/16230731/thumbnails/medium/content', self::$_document->getThumbnailURI());
	}

	public function testGetVersion()
	{
		$this->assertEquals('98', self::$_document->getVersion());
	}

}
