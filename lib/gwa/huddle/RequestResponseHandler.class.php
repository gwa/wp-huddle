<?php
namespace gwa\huddle;

class RequestResponseHandler
{
	private $_success;
	private $_code;
	private $_error;
	private $_persistance;

	public function __construct( Array $data, Persistance $persistance )
	{
		$this->_success     = isset($data['code']) ? true : false;
		$this->_code        = isset($data['code']) ? $data['code'] : null;
		$this->_error       = isset($data['error']) ? $data['error'] : null;
		$this->_persistance = $persistance;
	}

	public function handle()
	{
		if ($this->isSuccessful()) {
			$this->persist();
			return true;
		}
		return false;
	}

	public function isSuccessful()
	{
		return $this->_success;
	}

	public function getError()
	{
		return $this->_error;
	}

	public function persist()
	{
		$this->_persistance->set('auth_token', $this->_code);
		$this->_persistance->persist();
	}
}
