<?php
require_once __DIR__.'/../lib/gwa/huddle/XmlParser.class.php';

class GwaHuddleXmlParserTest extends PHPUnit_Framework_TestCase
{
	public function testParser()
	{
		$xml = file_get_contents(__DIR__.'/xml/User.xml');
		$parser = new \gwa\huddle\XmlParser($xml);
		$data = $parser->parse();
		$this->assertInstanceOf('\SimpleXMLElement', $data);

		$this->assertEquals((string) $data->profile->personal->displayname, 'Ian Cooper');
	}
}
