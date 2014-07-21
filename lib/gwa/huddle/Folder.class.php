<?php
namespace gwa\huddle;

require_once __DIR__.'/ApiCallCached.class.php';
require_once __DIR__.'/Api.class.php';
require_once __DIR__.'/Document.class.php';

class Folder
{
	private $_data;

	private $_folders;
	private $_documents;

	/**
	 * Fetch data from API and store as simpleXML
	 */
	public static function getFolderInstance( $uri )
	{
		$call = \gwa\huddle\ApiCallCached::create($uri);
		$data = $call->call();
		if (isset($data->ErrorCode)) {
			return null;
		}
		return new Folder($data);
	}

	public static function getFolderURI( $id )
	{
		return \gwa\huddle\Api::ENDPOINT.'/files/folders/'.$id;
	}

	public function __construct( $data=null )
	{
		$this->_data = $data;
	}

	public function getData()
	{
		return $this->_data;
	}

	public function getId()
	{
		return $this->_parseOutId($this->getURI());
	}

	public function getParentId()
	{
		return $this->getParentURI() ?
			$this->_parseOutId($this->getParentURI()) :
			null;
	}

	public function getParent()
	{
		if (!$uri = $this->getParentURI()) {
			return null;
		}
		return self::getFolderInstance($uri);
	}

	public function getParents()
	{
		$parents = array();
		$p = $this->getParent();
		while ($p) {
			$parents[] = $p;
			$p = $p->getParent();
		}
		return array_reverse($parents);
	}


	public function getParentsFromFolder( $folder )
	{
		$uri = $folder->getURI();
		$index = 0;
		$parents = $this->getParents();
		foreach ($parents as $parent) {
			if ($parent->getURI() == $uri) {
				break;
			}
			$index++;
		}
		return array_slice($parents, $index);
	}

	private function _parseOutId( $uri )
	{
		$pattern = '/(\d+)$/';
		if (!preg_match($pattern, $uri, $matches)) {
			return null;
		}
		return $matches[1];
	}

	public function getURI()
	{
		return $this->getLink('self');
	}

	public function getParentURI()
	{
		return $this->getLink('parent');
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
		return (string) $attr['displayName'];
	}

	public function getFolders()
	{
		if (!isset($this->_folders)) {
			$this->_folders = array();
			foreach ($this->getData()->folders->folder as $folder) {
				$this->_folders[] = new \gwa\huddle\Folder($folder);
			}
		}
		return $this->_folders;
	}

	public function getDocuments()
	{
		if (!isset($this->_documents)) {
			$this->_documents = array();
			if (isset($this->getData()->documents)) {
				foreach ($this->getData()->documents->document as $document) {
					$this->_documents[] = new \gwa\huddle\Document($document);
				}
			}
		}
		return $this->_documents;
	}

	public function getHTMLRepresentation()
	{
		return '<div class="media folder"><a href="?f='.$this->getId().'"><span></span>'.$this->getDisplayName().'</a></div>';
	}
}

