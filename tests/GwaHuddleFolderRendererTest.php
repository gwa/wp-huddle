<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../lib/gwa/huddle/Folder.class.php';
require_once __DIR__.'/../lib/gwa/huddle/FolderRenderer.class.php';

class GwaHuddleFolderRendererTest extends PHPUnit_Framework_TestCase
{
	private $_renderer;

	public function setUp()
	{
		$loader = new Twig_Loader_Filesystem(__DIR__.'/templates');
		$twig = new Twig_Environment($loader, array(
			'cache' => __DIR__.'/_cache/twig',
			'auto_reload' => true
		));
		$folder = new \gwa\huddle\Folder(simplexml_load_file(__DIR__.'/xml/FolderParent.xml'));
		$basefolder = new \gwa\huddle\Folder(simplexml_load_file(__DIR__.'/xml/FolderParent.xml'));
		$this->_renderer = new \gwa\huddle\FolderRenderer($folder, $basefolder, $twig);
	}

	public function testContructor()
	{
		$this->assertInstanceOf('\gwa\huddle\FolderRenderer', $this->_renderer);
	}

	public function testRender()
	{
		// test template simply outputs folder name
		$output = trim($this->_renderer->render('folder-test.twig.html'));
		$this->assertEquals('My folder', $output);
	}
}
