<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../lib/gwa/huddle/ApiCallCached.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Folder.class.php';

class GwaHuddleFolderTest extends PHPUnit_Framework_TestCase
{
	private static $_folder;

	public static function setUpBeforeClass()
	{
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$xml = file_get_contents(__DIR__.'/xml/Folder.xml');
		$api->setResponse($xml);
		\gwa\huddle\ApiCallCached::setApi($api);
		\gwa\huddle\ApiCallCached::setCacheDir(__DIR__.'/_cache/api');
		self::$_folder = \gwa\huddle\Folder::getFolderInstance('http://foo.com');
	}

	public static function tearDownAfterClass()
	{
		\gwa\huddle\ApiCallCached::clearEntireCache();
	}

	public function testGetFolder()
	{
		$this->assertInstanceOf('\gwa\huddle\Folder', self::$_folder);
	}

	public function testGetDisplayName()
	{
		$this->assertEquals('My folder', self::$_folder->getDisplayName());
	}

	public function testGetURI()
	{
		$this->assertEquals('files/folders/12345', self::$_folder->getURI());
	}

	public function testGetParentURI()
	{
		$this->assertEquals('files/folders/12344', self::$_folder->getParentURI());
	}

	public function testGetId()
	{
		$this->assertEquals('12345', self::$_folder->getId());
	}

	public function testGetParentId()
	{
		$this->assertEquals('12344', self::$_folder->getParentId());
	}

	public function testGetFolders()
	{
		$folders = self::$_folder->getFolders();
		$this->assertInstanceOf('\gwa\huddle\Folder', $folders[0]);
	}

	public function testGetDocuments()
	{
		$documents = self::$_folder->getDocuments();
		$this->assertInstanceOf('\gwa\huddle\Document', $documents[0]);
	}

	public function testGetParentNoParent()
	{
		$data = simplexml_load_file(__DIR__.'/xml/FolderParent.xml');
		$folder = new \gwa\huddle\Folder($data);
		$parent = $folder->getParent();
		$this->assertNull($parent);
	}

	public function testGetParent()
	{
		// set api repsonse when getParent is called
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$xml = file_get_contents(__DIR__.'/xml/FolderParent.xml');
		$api->setResponse($xml);
		\gwa\huddle\ApiCallCached::setApi($api);

		$parent = self::$_folder->getParent();
		$this->assertInstanceOf('\gwa\huddle\Folder', $parent);
		$this->assertNotSame(self::$_folder, $parent);
		$this->assertEquals($parent->getId(), 12344);
	}

	public function testGetParents()
	{
		// set api repsonse when getParent is called
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$xml = file_get_contents(__DIR__.'/xml/FolderParent.xml');
		$api->setResponse($xml);
		\gwa\huddle\ApiCallCached::setApi($api);

		$data = self::$_folder->getParents();
		$this->assertInternalType('array', $data);
		$this->assertEquals(1, count($data));
		$this->assertInstanceOf('\gwa\huddle\Folder', $data[0]);
		$this->assertEquals(12344, $data[0]->getId());
	}

}
