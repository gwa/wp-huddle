<?php
require_once __DIR__.'/../lib/gwa/huddle/ApiCallCached.class.php';
require_once __DIR__.'/../lib/gwa/huddle/Workspace.class.php';

class GwaHuddleWorkspaceTest extends PHPUnit_Framework_TestCase
{
	private static $_workspace;

	public static function setUpBeforeClass()
	{
		$api = new \gwa\huddle\ApiMock('foo', 'bar');
		$xml = file_get_contents(__DIR__.'/xml/Workspace.xml');
		$api->setResponse($xml);
		\gwa\huddle\ApiCallCached::setApi($api);
		\gwa\huddle\ApiCallCached::setCacheDir(__DIR__.'/_cache/api');
		self::$_workspace = \gwa\huddle\Workspace::getWorkspaceInstance('http://foo.com');
	}

	public static function tearDownAfterClass()
	{
		\gwa\huddle\ApiCallCached::clearEntireCache();
	}

	public function testGetWorkspace()
	{
		$this->assertInstanceOf('\gwa\huddle\Workspace', self::$_workspace);
	}

	public function testGetDisplayName()
	{
		$this->assertEquals('Infinite improbability drive', self::$_workspace->getDisplayName());
	}

	public function testGetURI()
	{
		$this->assertEquals('http://www.example.com', self::$_workspace->getURI());
	}

	public function testGetFolderURI()
	{
		$this->assertEquals('http://www.example.com/folder/1234', self::$_workspace->getFolderURI());
	}
}
