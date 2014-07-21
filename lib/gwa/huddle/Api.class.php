<?php
namespace gwa\huddle;

class Api
{
	const ENDPOINT_LOGIN = 'https://login.huddle.net';
	const ENDPOINT = 'https://api.huddle.net';

	private $_idclient;
	private $_redirecturi;
	private $_token;
	private $_cainfopath;

	public function __construct( $idclient, $redirecturi, $cainfopath=null )
	{
		$this->_idclient = $idclient;
		$this->_redirecturi = $redirecturi;
		$this->_cainfopath = $cainfopath;
	}

	/**
	 * @param  array $path
	 * @param  string $data - currently ignored!
	 * @return SimpleXMLElement
	 */
	public function call( $path, $data=null )
	{
		return simplexml_load_string($this->_curl($path, $data, array('Accept: application/xml')));
	}

	public function fetch( $path, $data=null )
	{
		return $this->_curl($path, $data);
	}

	private function _curl( $path, $data=null, $headers=array() )
	{
		if (!isset($this->_token)) {
			throw new \Exception('api: no token set');
		}

		if ($this->_token->isExpired()) {
			$this->_token->refresh();
		}
		$ch = curl_init();

		$headers[] = 'Authorization: OAuth2 '.$this->_token->getAccessToken();
		curl_setopt($ch, CURLOPT_URL, $path);
		if (isset($this->_cainfopath)) {
			curl_setopt($ch, CURLOPT_CAINFO, $this->_cainfopath);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		if (!$response = curl_exec($ch)) {
			throw new \Exception(curl_error($ch));
		}

		return $response;
	}

	/**
	 * @param  string $path
	 * @param  array $data
	 * @return stdClass
	 */
	public function login( $path, $data=null )
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, self::ENDPOINT_LOGIN.$path);
		if (isset($this->_cainfopath)) {
			curl_setopt($ch, CURLOPT_CAINFO, $this->_cainfopath);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		if (!$result = curl_exec($ch)) {
			throw new \Exception(curl_error($ch));
		}

		return json_decode($result);
	}

	/**
	 * Fetches access and refresh tokens using the user's auth code.
	 * @param  User                    $user
	 * @return stdClass `{ access_token, expires_in, refresh_token }`
	 */
	public function fetchAccessTokenForUser( User $user )
	{
		$d = array(
			'grant_type' => 'authorization_code',
			'client_id' => $this->_idclient,
			'redirect_uri' => $this->_redirecturi,
			'code' => $user->getAuthToken()
		);
		$data = $this->login('/token', $d);

		if (isset($data->error)) {
			throw new \Exception($data->error);
		}

		return $data;
	}

	public function refreshAccessToken( Token $token )
	{
		$d = array(
			'grant_type' => 'refresh_token',
			'client_id' => $this->_idclient,
			'refresh_token' => $token->getRefreshToken()
		);
		$data = $this->login('/token', $d);

		if (isset($data->error)) {
			switch ($data->error) {
				case 'invalid_grant':
					// fetch new access token
					$data = $this->fetchAccessTokenForUser($token->getUser());
					$token->update(
						$data->access_token,
						$data->expires_in,
						$data->refresh_token
					);
					break;
				default:
					throw new \Exception($data->error);
			}
		}

		return $data;
	}

	public function getAuthURI()
	{
		return self::ENDPOINT_LOGIN.'/request?response_type=code&client_id='.$this->_idclient.'&redirect_uri='.urlencode($this->_redirecturi);
	}

	public function setToken( $token )
	{
		$this->_token = $token;
	}
}
