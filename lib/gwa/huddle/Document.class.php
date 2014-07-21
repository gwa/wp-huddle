<?php
namespace gwa\huddle;

class Document
{
	const CACHE_MINUTES = 240;

	private static $_plugindiruri;

	public static function setPluginDirURI( $uri )
	{
		self::$_plugindiruri = $uri;
	}

	private $_data;

	/**
	 * Fetch data from API and store as simpleXML
	 */
	public static function getDocumentInstance( $uri )
	{
		$call = \gwa\huddle\ApiCallCached::create($uri, null, self::CACHE_MINUTES);
		$data = $call->call();
		if (isset($data->ErrorCode)) {
			return null;
		}
		return new Document($data);
	}

	public static function getDocumentURI( $id )
	{
		return \gwa\huddle\Api::ENDPOINT.'/files/documents/'.$id;
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

	public function getFolderId()
	{
		return $this->_parseOutId($this->getParentURI());
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

	public function getLinkNodeWithRel( $rel )
	{
		$xml = $this->getData();
		foreach ($xml->link as $link) {
			$attr = $link->attributes();
			if ($attr->rel == $rel) {
				return $link;
			}
		}
		return null;
	}

	public function getDisplayName()
	{
		$attr = $this->getData()->attributes();
		return (string) $attr['title'];
	}

	public function getDescription()
	{
		$attr = $this->getData()->attributes();
		return (string) $attr['description'];
	}

	public function getMimeType()
	{
		$node = $this->getLinkNodeWithRel('content');
		$attr = $node->attributes();
		return (string) $attr['type'];
	}

	public function getMimeTypeClassName()
	{
		switch ($this->getMimeType()) {
			case 'application/msword' :
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' :
				return 'doc';
			case 'application/excel' :
			case 'application/vnd.ms-excel' :
				return 'excel';
			case 'application/pdf' :
			case 'application/x-pdf' :
				return 'pdf';
			case 'application/zip' :
				return 'zip';
			case 'application/vnd.ms-powerpoint' :
				return 'ppt';
			default :
				return 'file';
		}
		// return str_replace(array('/', '.'), array('-', '_'), $this->getMimeType());
	}

	public function getFileName()
	{
		$node = $this->getLinkNodeWithRel('content');
		$attr = $node->attributes();
		return (string) $attr['title'];
	}

	public function getFileURI()
	{
		$node = $this->getLinkNodeWithRel('content');
		$attr = $node->attributes();
		return (string) $attr['href'];
	}

	public function getThumbnailURI()
	{
		$node = $this->getData()->thumbnails->thumbnail[0]->link;
		$attr = $node->attributes();
		return (string) $attr['href'];
	}

	public function hasThumbnail()
	{
		return isset($this->getData()->thumbnails->thumbnail[0]);
	}

	public function getHTMLRepresentation()
	{
		$downloadlink = self::$_plugindiruri.'/file.php?id='.$this->getId().'&v='.$this->getVersion();
		$thumbnail = $this->getThumbnailHTML();
		$thumbnailclass = $thumbnail ? ' has-thumbnail' : '';
		return '<div class="media '.$this->getMimeTypeClassName().$thumbnailclass.'"><a href="'.$downloadlink.'">'.$thumbnail.$this->getDisplayName().' <em>'.$this->getMimeType().'</em></a></div>';
	}

	public function getThumbnailHTML()
	{
		if (!$this->hasThumbnail()) {
			return '<span></span>';
		} else {
			return '<span><img src="'.self::$_plugindiruri.'/thumbnail.php?id='.$this->getId().'&v='.$this->getVersion().'" /></span>';
		}
	}

	public function getVersion()
	{
		return (string) $this->getData()->version;
	}
}

