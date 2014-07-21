<?php
namespace gwa\huddle;

require_once __DIR__.'/Api.class.php';
require_once __DIR__.'/Document.class.php';

class ThumbnailCached
{
	private static $__cachedir;
	private static $__api;

	/**
	 * Factory method.
	 * @param  Document $document
	 * @return ThumbnailCached
	 */
	public static function getThumbnailInstance( Document $document )
	{
		if (!isset(self::$__api) || !isset(self::$__cachedir)) {
			throw new \Exception('api or cachedir not set');
		}
		return new ThumbnailCached(
			self::$__api,
			self::$__cachedir,
			$document
		);
	}

	static public function clearEntireCache( $path=null )
	{
		if (!$path) {
			if (!isset(self::$__cachedir)) {
				throw new \Exception('cache dir not set');
			}
			$path = self::$__cachedir;
		}
		$files = glob($path.'/*');
		foreach ($files as $file) {
		  if (is_file($file)) {
		    unlink($file);
		  }
		}
	}

	public static function setApi( $api )
	{
		self::$__api = $api;
	}

	public static function setCacheDir( $cachedir )
	{
		self::$__cachedir = $cachedir;
	}

	private $_api;
	private $_cachedir;
	private $_document;
	private $_key;

	public function __construct( $api, $cachedir, Document $document )
	{
		$this->_api = $api;
		$this->_cachedir = $cachedir;
		$this->_document = $document;
	}

	public function hasThumbnail()
	{
		return $this->_document->hasThumbnail();
	}

	public function isCached()
	{
		if (!file_exists($this->getFilePath())) {
			return false;
		}
		return true;
	}

	public function getCacheVersion( $filepath )
	{
		$pattern = '/(\d+)\.[a-zA-Z]+$/';
		if (!preg_match($pattern, $filepath, $matches)) {
			throw new \Exception('invalid filepath');
		}
		return $matches[1];
	}

	private function _getCache()
	{
		return file_get_contents($this->getFilePath());
	}

	private function _setCache( $content )
	{
		$this->_testWritable();
		file_put_contents($this->getFilePath(), $content);
	}

	public function getKey()
	{
		if (isset($this->_key)) {
			return $this->_key;
		}
		return $this->_key = md5($this->_document->getURI());
	}

	public function getFilePath( $version=null )
	{
		if (!$version) {
			$version = $this->_document->getVersion();
		}
		return $this->_cachedir.'/'.$this->getKey().'-'.$version.$this->getFileExtension();
	}

	public function getFileExtension()
	{
		return '.jpg';
	}

	public function getHeaders()
	{
		return array(
			'Content-type: image/jpeg'
		);
	}

	public function getContent()
	{
		if (!$this->hasThumbnail()) {
			// TODO return standard thumbnail content
		}
		if (!$this->isCached()) {
			// get from api and store
			$content = $this->_api->fetch($this->_document->getThumbnailURI());
			$this->_setCache($content);
			return $content;
		} else {
			return $this->_getCache();
		}
	}

	public function output()
	{
		foreach ($this->getHeaders() as $header) {
			header($header);
		}
		echo $this->getContent();
	}

	private function _testWritable()
	{
		$filepath = $this->getFilePath();
		if (file_exists($filepath)) {
			$path = $filepath;
		} else {
			$path = dirname($filepath);
			if (!file_exists($path) && !mkdir($path)) {
				throw new \Exception('not_writable: '.$path);
			}
		}
		if (!is_writable($path)) {
			throw new \Exception('not_writable: '.$path);
		}
	}
}
