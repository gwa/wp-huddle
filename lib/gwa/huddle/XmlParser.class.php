<?php
namespace gwa\huddle;

class XmlParser
{
	private $_xmlstring;
	private $_xml;

	public function __construct( $xmlstring )
	{
		$this->_xmlstring = $xmlstring;
	}

	public function parse()
	{
		return $this->_xml = simplexml_load_string($this->_xmlstring);
	}
}
