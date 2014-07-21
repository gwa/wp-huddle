<?php
namespace gwa\huddle;

require_once __DIR__.'/XmlParser.class.php';

class ApiCallCached
{
	private static $__cachedir;
	private static $__api;

	/**
	 * Factory method.
	 * @param  string  $path
	 * @param  array  $data
	 * @param  integer $cacheminutes
	 * @return ApiCallCached
	 */
	public static function create( $path='', $data=null, $cacheminutes=30 )
	{
		if (!isset(self::$__api) || !isset(self::$__cachedir)) {
			throw new \Exception('api or cachedir not set');
		}
		return new ApiCallCached(
			self::$__api,
			self::$__cachedir,
			$path,
			$data,
			$cacheminutes
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
	private $_path;
	private $_data;
	private $_cacheminutes;
	private $_cachedir;
	private $_key;

	public function __construct( Api $api, $cachedir, $path, $data=null, $cacheminutes=30 )
	{
		$this->_api = $api;
		$this->_cachedir = $cachedir;
		$this->_path = $path;
		$this->_data = $data;
		$this->_cacheminutes = $cacheminutes;
	}

	public function isCached()
	{
		$filepath = $this->_getFilePath();
		if (!file_exists($filepath)) {
			return false;
		}
		return filemtime($filepath) > time()-($this->_cacheminutes*60);
	}

	public function clearCache()
	{
		$filepath = $this->_getFilePath();
		if (!file_exists($filepath)) {
			return;
		}
		unlink($filepath);
	}

	/**
	 * Call retrieves data as a SimpleXMLElement
	 * @method call
	 * @return SimpleXMLElement
	 */
	public function call()
	{
		if ($this->isCached()) {
			return $this->_getCachedXML();
		}
		$response = $this->_api->call($this->_path, $this->_data);
		$this->_setCachedXML($response);
		return $response;
	}

	private function _getCachedXML()
	{
		$p = new \gwa\huddle\XmlParser(file_get_contents($this->_getFilePath()));
		return $p->parse();
	}

	private function _setCachedXML( $content )
	{
		$this->_testWritable();
		file_put_contents($this->_getFilePath(), $content->asXML());
	}

	public function getKey()
	{
		if (isset($this->_key)) {
			return $this->_key;
		}
		if (isset($this->_data)) {
			$s = $this->_path . http_build_query($this->_data) . $this->_cacheminutes;
		} else {
			$s = $this->_path . $this->_cacheminutes;
		}
		return $this->_key = md5($s);
	}

	/**
	 * Only used for testing!
	 * @param  [int] $timestamp
	 */
	public function touch( $timestamp )
	{
		touch($this->_getFilePath(), $timestamp);
	}

	private function _getFilePath()
	{
		return $this->_cachedir.'/'.$this->getKey();
	}

	private function _testWritable()
	{
		$filepath = $this->_getFilePath();
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
