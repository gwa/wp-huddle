<?php
namespace gwa\huddle;

require_once __DIR__.'/Persistance.class.php';

class PersistanceMock extends Persistance
{
	public function __construct( $filepath )
	{
		// do nothing
	}

	public function persist()
	{
		// do nothing
	}

	public function purge()
	{
		// do nothing
	}

	protected function _testWritable()
	{
		return true;
	}

	public function setData( array $data )
	{
		$this->_data = $data;
	}
}
