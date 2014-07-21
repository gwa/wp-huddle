<?php
namespace gwa\huddle;

class ApiMock extends Api
{
	private $_responses = array();
	private $_rawdata = array();

	public function call( $path, $data=null )
	{
		return array_shift($this->_responses);
	}

	public function login( $path, $data=null )
	{
		return array_shift($this->_responses);
	}

	public function fetch( $path, $data=null )
	{
		return array_shift($this->_rawdata);
	}

	/**
	 * @param [mixed] $response XML String or stdClass
	 */
	public function setResponse( $response )
	{
		if (is_string($response)) {
			$p = new XmlParser($response);
			$this->_responses[] = $p->parse();
		} else {
			$this->_responses[] = $response;
		}
	}

	/**
	 * Set data to be returned by `fetch`.
	 * @method setRawData
	 * @param  string     $data
	 */
	public function setRawData( $data )
	{
		$this->_rawdata[] = $data;
	}
}
