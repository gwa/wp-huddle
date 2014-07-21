<?php
namespace gwa\huddle;

class Token
{
	private $_access_token;

	private $_expires;

	private $_refresh_token;

	private $_persistance;

	private $_api;

	private $_user;

	public static function getTokenInstance( Persistance $persistance, Api $api, User $user )
	{
		if (
			!$persistance->get('access_token') ||
			!$persistance->get('expires') ||
			!$persistance->get('refresh_token')
		) {
			return null;
		}
		return new Token(
			$persistance->get('access_token'),
			$persistance->get('expires'),
			$persistance->get('refresh_token'),
			$persistance,
			$api,
			$user
		);
	}

	public function __construct(
		$access_token,
		$expires,
		$refresh_token,
		$persistance=null,
		$api=null,
		$user=null
	)
	{
		$this->_access_token = $access_token;
		$this->_expires = $expires;
		$this->_refresh_token = $refresh_token;
		$this->_persistance = $persistance;
		$this->_api = $api;
		$this->_user = $user;
	}

	public function getAccessToken()
	{
		if ($this->isExpired()) {
			throw new \Exception('expired');
		}
		return $this->_access_token;
	}

	public function getRefreshToken()
	{
		return $this->_refresh_token;
	}

	public function isExpired()
	{
		if (!isset($this->_expires)) {
			return true;
		}
		// add 5 seconds just in case
		return $this->_expires < time() + 5;
	}

	public function getExpiresIn()
	{
		return $this->_expires - time();
	}

	public function setUser( User $user )
	{
		$this->_user = $user;
	}

	public function getUser()
	{
		return $this->_user;
	}

	public function refresh()
	{
		$data = $this->_api->refreshAccessToken($this);
		$this->_access_token = $data->access_token;
		$this->_expires = time() + $data->expires_in;
		$this->_refresh_token = $data->refresh_token;
		$this->persist();
	}

	public function update( $access_token, $expires, $refresh_token )
	{
		$this->_access_token  = $access_token;
		$this->_expires       = $expires;
		$this->_refresh_token = $refresh_token;
		$this->persist();
	}

	public function persist()
	{
		if (!isset($this->_persistance)) {
			return;
		}
		$this->_persistance->set('access_token', $this->_access_token);
		$this->_persistance->set('expires', $this->_expires);
		$this->_persistance->set('refresh_token', $this->_refresh_token);
		$this->_persistance->persist();
	}
}
