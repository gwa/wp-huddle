<?php
namespace gwa\huddle;

class Persistance
{
	protected $_filepath;
	protected $_data;

	public function __construct( $filepath )
	{
		$this->_filepath = $filepath;
		$this->_readdata();
	}

	protected function _readdata()
	{
		if (!file_exists($this->_filepath)) {
			$this->_data = array();
			return;
		}
		$this->_data = json_decode(file_get_contents($this->_filepath), true);
	}

	public function getData()
	{
		return $this->_data;
	}

	public function get( $key )
	{
		return isset($this->_data[$key]) ? $this->_data[$key] : null;
	}

	public function set( $key, $value )
	{
		return $this->_data[$key] = $value;
	}

	public function persist()
	{
		$this->_testWritable();
		file_put_contents($this->_filepath, json_encode($this->_data));
	}

	public function purge()
	{
		if (file_exists($this->_filepath)) {
			unlink($this->_filepath);
		}
	}

	protected function _testWritable()
	{
		if (file_exists($this->_filepath)) {
			$path = $this->_filepath;
		} else {
			$path = dirname($this->_filepath);
		}
		if (!is_writable($path)) {
			throw new \Exception('not_writable: '.$path);
		}
	}
}
