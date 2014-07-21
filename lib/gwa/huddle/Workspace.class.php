<?php
namespace gwa\huddle;

require_once __DIR__.'/ApiCallCached.class.php';
require_once __DIR__.'/Folder.class.php';

class Workspace
{
	private $_data;

	private $_folder;

	/**
	 * Fetch data from API and store as simpleXML
	 */
	public static function getWorkspaceInstance( $uri )
	{
		$call = \gwa\huddle\ApiCallCached::create($uri);
		// TODO try/catch for error
		$data = $call->call();
		return new Workspace($data);
	}

	public function __construct( $data=null )
	{
		$this->_data = $data;
	}

	public function getData()
	{
		return $this->_data;
	}

	public function getURI()
	{
		return $this->getLink('self');
	}

	public function getLink( $rel )
	{
		$xml = $this->getData();
		foreach ($xml->link as $link) {
			$attr = $link->attributes();
			if ($attr->rel == $rel) {
				return (string) $attr['href'];
			}
		}
		return null;
	}

	public function getDisplayName()
	{
		$attr = $this->getData()->attributes();
		return (string) $attr['title'];
	}

	public function getFolderURI()
	{
		return $this->getLink('documentLibrary');
	}

	public function getFolder()
	{
		if (!isset($this->_folder)) {
			$this->_folder = \gwa\huddle\Folder::getFolderInstance($this->getFolderURI());
		}
		return $this->_folder;
	}
}
