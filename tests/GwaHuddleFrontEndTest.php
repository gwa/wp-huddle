<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../lib/gwa/huddle/FrontEnd.class.php';

class GwaHuddleFrontEndTest extends PHPUnit_Framework_TestCase
{
    private $_frontend;

    public function setUp()
    {
        $this->_frontend = new \gwa\huddle\FrontEnd(array(), __DIR__);
    }

    public function testConstruct()
    {
        $this->assertInstanceOf('\gwa\huddle\FrontEnd', $this->_frontend);
    }

    public function testGetTwig()
    {
        $twig = $this->_frontend->getTwig();
        $this->assertInstanceOf('\Twig_Environment', $twig);
        $twig2 = $this->_frontend->getTwig();
        $this->assertSame($twig, $twig2);
    }
}
