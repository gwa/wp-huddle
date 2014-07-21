<?php
require_once __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/../lib/gwa/huddle/Api.class.php';
require_once __DIR__.'/../lib/gwa/huddle/ApiMock.class.php';
require_once __DIR__.'/../lib/gwa/huddle/ApiCallCached.class.php';
require_once __DIR__.'/../lib/gwa/huddle/PersistanceMock.class.php';

require_once __DIR__.'/../lib/gwa/huddle/AdminOptionPage.class.php';

class GwaHuddleAdminOptionPageTest extends PHPUnit_Framework_TestCase
{
    private static $_twig;

    public static function setUpBeforeClass()
    {
        $loader = new \Twig_Loader_Filesystem(__DIR__.'/templates');
        self::$_twig = new \Twig_Environment($loader, array(
            'cache' => __DIR__.'/_cache/twig',
            'auto_reload' => true
        ));
    }

    public static function tearDownAfterClass()
    {
        \gwa\huddle\ApiCallCached::setApi(null);
        \gwa\huddle\ApiCallCached::setCacheDir(null);
    }

    public function testConstruct()
    {
        $api = new \gwa\huddle\ApiMock('foo', 'bar');

        $persistance = new \gwa\huddle\PersistanceMock('foo');

        $adminpage = new \gwa\huddle\AdminOptionPage(
            array(),
            self::$_twig,
            $persistance,
            $api,
            __DIR__
        );
        $this->assertInstanceOf('\gwa\huddle\AdminOptionPage', $adminpage);
    }

    public function testSetWorkspaceThroughGet()
    {
        $uri = 'http://www.example.com';
        $api = new \gwa\huddle\ApiMock('foo', 'bar');
        $persistance = new \gwa\huddle\PersistanceMock('foo');
        $get = array('workspace' => $uri);
        $this->assertNull($persistance->get('workspace'));
        $adminpage = new \gwa\huddle\AdminOptionPage(
            $get,
            self::$_twig,
            $persistance,
            $api,
            __DIR__
        );
        $this->assertEquals($persistance->get('workspace'), $uri);
    }

    public function testGetWorkspace()
    {
        $uri = 'http://www.example.com';

        $api = new \gwa\huddle\ApiMock('foo', 'bar');
        $xml = file_get_contents(__DIR__.'/xml/Workspace.xml');
        $api->setResponse($xml);

        $persistance = new \gwa\huddle\PersistanceMock('foo');
        $persistance->setData(array('workspace' => $uri));

        $adminpage = new \gwa\huddle\AdminOptionPage(
            array(),
            self::$_twig,
            $persistance,
            $api,
            __DIR__
        );

        $workspace = $adminpage->getWorkspace();
        $folder = $adminpage->getFolder();
        $this->assertInstanceOf('\gwa\huddle\Workspace', $workspace);
        $this->assertNull($folder);
    }

    public function testGetFolder()
    {
        $uri = 'http://www.example.com';

        $api = new \gwa\huddle\ApiMock('foo', 'bar');
        $xml = file_get_contents(__DIR__.'/xml/Workspace.xml');
        $api->setResponse($xml);

        $persistance = new \gwa\huddle\PersistanceMock('foo');
        $persistance->setData(array('folder' => $uri));

        $adminpage = new \gwa\huddle\AdminOptionPage(
            array(),
            self::$_twig,
            $persistance,
            $api,
            __DIR__
        );

        $folder = $adminpage->getFolder();
        $this->assertInstanceOf('\gwa\huddle\Folder', $folder);
    }
}
