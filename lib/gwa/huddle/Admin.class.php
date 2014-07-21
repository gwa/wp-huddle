<?php
namespace gwa\huddle;

class Admin
{
    const ERR_NO_API = 'no_api';

    private $_get;
    private $_rootdir;
    private $_api;
    private $_persistance;
    private $_user;
    private $_twig;

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

    /**
     * @brief Adds WP hooks
     */
    private function _initWPActions()
    {
        if (!function_exists('add_action')) {
            return;
        }
        add_action('admin_menu',                           array($this, 'addMenu'));
        add_action('admin_enqueue_scripts',                array($this, 'addHuddleMetaBoxJS'));
        add_action('add_meta_boxes_post',                  array($this, 'addHuddleMetaBox'));
        add_action('save_post',                            array($this, 'handlePostSave'));
        add_action('wp_ajax_gwahuddle_get_workspaces',     array($this, 'getWorkspacesJSON'));
        add_action('wp_ajax_gwahuddle_get_folder',         array($this, 'getFolderJSON'));
    }

    /**
     * @see http://codex.wordpress.org/Function_Reference/add_meta_box
     */
    public function addHuddleMetaBox( $post )
    {
        add_meta_box(
            'gwa-huddle',
            'GWA: Huddle',
            array($this, 'renderHuddleMetaBox'),
            'post',
            'normal',
            'default',
            null
        );
    }

    /**
     * @brief Renders the meta box on post edit page.
     */
    public function renderHuddleMetaBox( $post, $metabox )
    {
        if (!$this->getUser()->isAuthorized()) {
            echo 'Please authorize a Huddle User in options.';
            return;
        }

        require_once __DIR__.'/../wordpress/MultiMetaOption.class.php';
        $multi = new \gwa\wordpress\MultiMetaOption($post, 'huddledoc');

        $markup =  '';
        $markup .= '<div class="gwahuddle">';
        $markup .= '<h4>Selected documents</h4>';
        $markup .= '<ul id="gwahuddle-selected">';
        foreach ($multi->getSetOptions() as $option) {
            $document = \gwa\huddle\Document::getDocumentInstance(
                \gwa\huddle\Document::getDocumentURI($option)
            );
            if ($document) {
                $markup .= '<li><div class="'.$document->getMimeTypeClassName().'"><input type="hidden" name="gwawp_huddledoc[]" value="'.$document->getId().'" />'.$document->getThumbnailHTML().' '.$document->getDisplayName().'</div></li>';
            }
        }
        $markup .= '</ul>';
        $markup .= '<div id="gwahuddle-tree" class="tree">';
        $markup .= '<a class="button" id="gwahuddle-add">Add documents from Huddle</a>';
        $markup .= '</div>';
        $markup .= '</div>';
        echo $markup;
    }

    /**
     * Adds javascript to edit page in admin.
     * @see http://codex.wordpress.org/Function_Reference/wp_enqueue_script
     */
    public function addHuddleMetaBoxJS( $hook )
    {
        // only show on post.php
        if ('post.php' != $hook) {
            return;
        }
        wp_enqueue_style(
            'gw_huddle_admin',
            plugins_url().'/gw_huddle/assets/css/gw_huddle_admin.css'
        );
        wp_enqueue_script(
            'gw_huddle_admin',
            plugins_url().'/gw_huddle/assets/js/gw_huddle_admin.js',
            array('jquery'),
            '1.0.0'
        );
        wp_localize_script(
            'gw_huddle_admin',
            'ajax_object',
            array(
                'ajax_url' => admin_url('admin-ajax.php')
            )
        );
    }

    /* -------- SAVE ------------------------ */

    public function handlePostSave( $post_id )
    {
        require_once __DIR__.'/../wordpress/MultiMetaOption.class.php';
        $multi = new \gwa\wordpress\MultiMetaOption(null, 'huddledoc');
        $multi->handlePost($post_id, $_POST);
    }

    /* -------- AJAX ------------------------ */

    public function getWorkspacesJSON()
    {
        $data = new \stdClass;
        if (!$this->getUser()->isAuthorized()) {
            $data->error = 'unauthorized';
            echo json_encode($data);
            exit;
        }
        $workspaces = $this->getUser()->getWorkspaces();
        $data->workspaces = array();
        foreach($workspaces as $workspace) {
            $data->workspaces[] = array(
                'displayname' => $workspace->getDisplayName(),
                'idfolder' => $workspace->getFolder()->getId()
            );
        }
        echo json_encode($data);
        exit;
    }

    public function getFolderJSON()
    {
        $idfolder = $_POST['idfolder'];
        $data = new \stdClass;
        if (!preg_match('/^\d+$/', $idfolder)) {
            $data->error = 'invalid id';
            echo json_encode($data);
            exit;
        }
        if (!$this->getUser()->isAuthorized()) {
            $data->error = 'unauthorized';
            echo json_encode($data);
            exit;
        }
        if (!$folder = Folder::getFolderInstance(Folder::getFolderURI($idfolder))) {
            $data->error = 'folder not found';
            echo json_encode($data);
            exit;
        }
        $data->idparent = $folder->getParentId();
        $data->displayname = $folder->getDisplayName();
        $folders = $folder->getFolders();
        $data->folders = array();
        foreach($folders as $folder) {
            $data->folders[] = array(
                'displayname' => $folder->getDisplayName(),
                'idfolder' => $folder->getId()
            );
        }
        $documents = $folder->getDocuments();
        $data->documents = array();
        foreach($documents as $document) {
            $data->documents[] = array(
                'displayname' => $document->getDisplayName(),
                'iddocument' => $document->getId(),
                'classname' => $document->getMimeTypeClassName(),
                'thumbnail' => $document->getThumbnailHTML()
            );
        }
        echo json_encode($data);
        exit;
    }

    /* -------- Options ------------------------ */

    /**
     * This will create a menu item under the option menu
     * @see http://codex.wordpress.org/Function_Reference/add_options_page
     */
    public function addMenu()
    {
        add_options_page('GWA Huddle Options', 'GWA Huddle', 'manage_options', 'gwa-huddle-admin', array($this, 'optionPage'));
    }

    /**
     * This is where you add all the html and php for your option page
     * @see http://codex.wordpress.org/Function_Reference/add_options_page
     */
    public function optionPage()
    {
        require_once(__DIR__.'/AdminOptionPage.class.php');
        require_once(__DIR__.'/Persistance.class.php');
        require_once(__DIR__.'/Api.class.php');
        $persistance = new \gwa\huddle\Persistance($this->_rootdir.'/_data/data.json');
        $api = new \gwa\huddle\Api('NewsroomGore-tex', 'http://gwadev.de/HuddleRedirect');
        $optionpage = new AdminOptionPage(
            $this->_get,
            $this->getTwig(),
            $this->getPersistance(),
            $this->getApi(),
            $this->_rootdir
        );
        echo $optionpage->render();
    }

    private function _initTwig()
    {
        $loader = new \Twig_Loader_Filesystem($this->_rootdir.'/templates');
        $this->_twig = new \Twig_Environment($loader, array(
            'cache' => $this->_rootdir.'/_cache/twig',
            'auto_reload' => true
        ));
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
}
