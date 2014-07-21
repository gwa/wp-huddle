<?php
namespace gwa\huddle;

require_once __DIR__.'/Persistance.class.php';
require_once __DIR__.'/Api.class.php';
require_once __DIR__.'/ApiCallCached.class.php';
require_once __DIR__.'/User.class.php';
require_once __DIR__.'/Workspace.class.php';

/**
 * @brief The plugin options page.
 */
class AdminOptionPage
{
    private $_twig;
    private $_persistance;
    private $_api;
    private $_rootdir;
    private $_user;

    public function __construct( $get, \Twig_Environment $twig, \gwa\huddle\Persistance $persistance, \gwa\huddle\Api $api, $rootdir )
    {
        $this->_twig = $twig;
        $this->_persistance = $persistance;
        $this->_api = $api;
        $this->_rootdir = $rootdir;
        $this->_user = \gwa\huddle\User::getUserInstance($persistance, $api);

        \gwa\huddle\ApiCallCached::setApi($this->_api);
        \gwa\huddle\ApiCallCached::setCacheDir($this->_rootdir.'/_cache/api');

        $this->handleGet($get);
    }

    /**
     * Checks if `code` was passed in the `get` array.
     * If so, persists the auth code.
     * @method handleGet
     * @param array $get
     * @return [type]
     */
    public function handleGet( $get )
    {
        if (!isset($get) || !is_array($get)) {
            return;
        }
        if (array_key_exists('code', $get) || array_key_exists('error', $get)) {
            if ($this->_handleAuthResponse($get)) {
                $this->_user = \gwa\huddle\User::getUserInstance($this->_persistance, $this->_api);
            }
            // TODO handle error
        }
        $this->_saveToPersistance('workspace', $get);
        $this->_saveToPersistance('folder', $get);
    }

    protected function _saveToPersistance( $key, array $data )
    {
        if (array_key_exists($key, $data)) {
            $this->_persistance->set($key, $data[$key]=='NULL' ? null : $data[$key]);
            $this->_persistance->persist();
        }
    }

    private function _handleAuthResponse( $data )
    {
        require_once __DIR__.'/RequestResponseHandler.class.php';
        $handler = new RequestResponseHandler($data, $this->_persistance);
        return $handler->handle();
    }

    public function render()
    {
        if (!$this->_user->isAuthorized()) {
            return $this->_twig->render(
                'admin-options-not-authorized.twig.html',
                array(
                    'authurl' => $this->_api->getAuthURI()
                )
            );
        }

        $token = $this->_user->getAccessToken();
        $this->_api->setToken($token);

        return $this->_twig->render(
            'admin-options-authorized.twig.html',
            array(
                'user' => $this->getUser(),
                'token_expired' => $token->isExpired() ? 'expired' : 'active',
                'token_expires_in' => $token->getExpiresIn(),
                'workspace' => $this->getWorkspace(),
                'folder' => $this->getFolder()
            )
        );
    }

    public function getUser()
    {
        return $this->_user;
    }

    /**
     * @return \gwa\huddle\Workspace
     */
    public function getWorkspace()
    {
        if (!$uri = $this->_persistance->get('workspace')) {
            return null;
        }
        return \gwa\huddle\Workspace::getWorkspaceInstance($uri);
    }

    /**
     * @return \gwa\huddle\Folder
     */
    public function getFolder()
    {
        if (!$uri = $this->_persistance->get('folder')) {
            return null;
        }
        return \gwa\huddle\Folder::getFolderInstance($uri);
    }
}
