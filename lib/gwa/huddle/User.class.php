<?php
namespace gwa\huddle;

require_once __DIR__.'/Persistance.class.php';
require_once __DIR__.'/Api.class.php';
require_once __DIR__.'/Token.class.php';
require_once __DIR__.'/ApiCallCached.class.php';
require_once __DIR__.'/Workspace.class.php';

class User
{
	/**
	 * Code returned after initial authorization.
	 * @var string
	 */
	private $_authtoken;

	/**
	 * @var Token
	 */
	private $_accesstoken;

	/**
	 * @var Persistance
	 */
	private $_persistance;

	private $_api;

	private $_data;

	static public function getUserInstance( Persistance $persistance, $api )
	{
		return new User($persistance->get('auth_token'), $persistance, $api);
	}

	public function __construct( $authtoken=null, $persistance=null, $api=null )
	{
		$this->_authtoken = $authtoken;
		$this->_persistance = $persistance;
		$this->_api = $api;
	}

	public function getAuthToken()
	{
		return $this->_authtoken;
	}

	public function setAuthToken( $authtoken )
	{
		$this->_authtoken = $authtoken;
	}

	public function isAuthorized()
	{
		return isset($this->_authtoken);
	}

	public function getAccessToken()
	{
		if (isset($this->_accesstoken)) {
			return $_accesstoken;
		}
		if (!isset($this->_persistance)) {
			throw new \Exception('no_persistance_set');
		}
		$this->_accesstoken = Token::getTokenInstance($this->_persistance, $this->_api, $this);
		if (!$this->_accesstoken) {
			$this->fetchAccessToken();
		}
		return $this->_accesstoken;
	}

	public function fetchAccessToken()
	{
		if (!$this->isAuthorized()) {
			throw new \Exception('user_unauthorized');
		}

		// throws exception on error
		$data = $this->_api->fetchAccessTokenForUser($this);

		$token = new Token(
			$data->access_token,
			time() + $data->expires_in,
			$data->refresh_token,
			$this->_persistance,
			$this->_api
		);
		$token->persist();
		$this->_accesstoken = $token;
		return $token;
	}

	/**
	 * Fetch user data from API and store as simpleXML
	 */
	private function _fetchData()
	{
		$call = \gwa\huddle\ApiCallCached::create(\gwa\huddle\Api::ENDPOINT.'/entry');
		// TODO try/catch for error
		$data = $call->call();
		return $data;
	}

	public function getData()
	{
		if (!isset($this->_data)) {
			$this->_data = $this->_fetchData();
		}
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
		return (string) $this->getData()->profile->personal->displayname;
	}

	public function getWorkspaces()
	{
		if (!isset($this->_workspaces)) {
			$this->_workspaces = array();
			foreach ($this->getData()->membership->workspaces->workspace as $workspace) {
				$this->_workspaces[] = new \gwa\huddle\Workspace($workspace);
			}
		}
		return $this->_workspaces;
	}
}
