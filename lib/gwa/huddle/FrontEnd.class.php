<?php
namespace gwa\huddle;

require_once(__DIR__.'/Persistance.class.php');
require_once(__DIR__.'/Api.class.php');
require_once(__DIR__.'/ApiCallCached.class.php');
require_once(__DIR__.'/User.class.php');
require_once(__DIR__.'/Workspace.class.php');
require_once(__DIR__.'/Folder.class.php');
require_once(__DIR__.'/FolderRenderer.class.php');

class FrontEnd
{
    const ERR_NO_API = 'no_api';
    const ERR_NO_WORKSPACE = 'no_workspace';

    const VERSION = '1.0';

    private $_get;
    private $_rootdir;
    private $_persistane;
    private $_api;
    private $_user;
    private $_twig;
    private $_basefolder;

    /**
     * @param  array $get injected `$_GET`
     * @param  string $rootdir
     */
    public function __construct( $get, $rootdir )
    {
        $this->_get = $get;
        $this->_rootdir = $rootdir;
        $this->_initWPActions();
    }

    private function _initWPActions()
    {
        if (!function_exists('add_action')) {
            return;
        }
        add_action('wp_enqueue_scripts', array($this, 'addHuddleCSS'));
    }

    private function _initPersistance()
    {
        $this->_persistance = new \gwa\huddle\Persistance($this->_rootdir.'/_data/data.json');
    }

    private function _initUser()
    {
        $this->_user = \gwa\huddle\User::getUserInstance($this->getPersistance(), $this->getApi());
        $this->_api->setToken($this->_user->getAccessToken());
    }

    private function _initTwig()
    {
        $loader = new \Twig_Loader_Filesystem($this->_rootdir.'/templates');
        $this->_twig = new \Twig_Environment($loader, array(
            'cache' => $this->_rootdir.'/_cache/twig',
            'auto_reload' => true
        ));
        $this->_twig->addExtension(new \Twig_Extensions_Extension_Text());
    }

    /* -------- */

    public function addHuddleCSS()
    {
        wp_enqueue_style('gw_huddle_frontend', plugins_url().'/gw_huddle/assets/css/gw_huddle.css');
    }

    /* -------- */

    public function getBaseFolder()
    {
        if (!isset($this->_basefolder)) {
            if ($uri = $this->getPersistance()->get('folder')) {
                $this->_basefolder = \gwa\huddle\Folder::getFolderInstance($uri);
            } elseif ($uri = $this->getPersistance()->get('workspace')) {
                $workspace = \gwa\huddle\Workspace::getWorkspaceInstance($uri);
                $this->_basefolder =  $workspace->getFolder();
            } else {
                throw new \Exception(self::ERR_NO_WORKSPACE);
            }
        }
        return $this->_basefolder;
    }

    public function getFolderById( $idfolder )
    {
        $uri = \gwa\huddle\Folder::getFolderURI($idfolder);
        return \gwa\huddle\Folder::getFolderInstance($uri);
    }

    public function getFolderHTML( Folder $folder )
    {
        $renderer = new FolderRenderer($folder, $this->getBaseFolder(), $this->getTwig());
        return $renderer->render();
    }

    public function getDocumentById( $iddocument )
    {
        $uri = \gwa\huddle\Document::getDocumentURI($iddocument);
        return \gwa\huddle\Document::getDocumentInstance($uri);
    }

    public function getDocumentHTML( Document $document )
    {
        return $document->getHTMLRepresentation();
    }

    /* -------- */

    public function getUser()
    {
        if (!isset($this->_user)) {
            $this->_initUser();
        }
        return $this->_user;
    }

    public function setUser( $user )
    {
        $this->_user = $user;
    }

    public function getTwig()
    {
        if (!isset($this->_twig)) {
            $this->_initTwig();
        }
        return $this->_twig;
    }

    public function setTwig( $twig )
    {
        $this->_twig = $twig;
    }

    public function getApi()
    {
        if (!isset($this->_api)) {
            throw new \Exception(self::ERR_NO_API);
        }
        return $this->_api;
    }

    public function setApi( $api )
    {
        $this->_api = $api;
    }

    public function getPersistance()
    {
        if (!isset($this->_persistance)) {
            $this->_initPersistance();
        }
        return $this->_persistance;
    }

    public function setPersistance( $persistance )
    {
        $this->_persistance = $persistance;
    }
}
