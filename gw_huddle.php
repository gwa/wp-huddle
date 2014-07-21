<?php
/**
 * Plugin Name: GWA Huddle
 * Plugin URI: http://www.greatwhiteark.com
 * Description: Provides access to the Huddle API
 * Version: 1.0
 * Author: Timothy Groves
 * Author URI: http://www.greatwhiteark.com
 * License: A "Slug" license name e.g. GPL2
 */

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/lib/gwa/huddle/Api.class.php';
require_once __DIR__.'/lib/gwa/huddle/ApiCallCached.class.php';
require_once __DIR__.'/lib/gwa/huddle/Persistance.class.php';
require_once __DIR__.'/lib/gwa/huddle/FrontEnd.class.php';
require_once __DIR__.'/lib/gwa/huddle/Admin.class.php';
require_once(__DIR__.'/lib/gwa/huddle/Document.class.php');

$gwahuddleapi = new \gwa\huddle\Api(
    'NewsroomGore-tex',
    'http://gwadev.de/HuddleRedirect',
    __DIR__.'/cacert.pem'
);
$gwahuddlepersistance = new \gwa\huddle\Persistance(__DIR__.'/_data/data.json');
$gwahuddleuser = \gwa\huddle\User::getUserInstance($gwahuddlepersistance, $gwahuddleapi);
if ($gwahuddleuser->isAuthorized()) {
    $gwahuddleapi->setToken($gwahuddleuser->getAccessToken());
}

\gwa\huddle\Document::setPluginDirURI(plugins_url().'/gw_huddle');

\gwa\huddle\ApiCallCached::setApi($gwahuddleapi);
\gwa\huddle\ApiCallCached::setCacheDir(__DIR__.'/_cache/api');

$gwahuddlefrontend = new \gwa\huddle\FrontEnd($_GET, __DIR__);
$gwahuddlefrontend->setApi($gwahuddleapi);
$gwahuddlefrontend->setPersistance($gwahuddlepersistance);
$gwahuddlefrontend->setUser($gwahuddleuser);

$gwahuddleadmin = new \gwa\huddle\Admin($_GET, __DIR__);
$gwahuddleadmin->setApi($gwahuddleapi);
$gwahuddleadmin->setPersistance($gwahuddlepersistance);
$gwahuddleadmin->setUser($gwahuddleuser);

function gwahuddle_folder_HTML( $idfolder=null ) {
    global $gwahuddlefrontend;

    if (!$idfolder) {
        if (isset($_GET['f']) && preg_match('/^\d+$/', $_GET['f'])) {
            $idfolder = $_GET['f'];
        }
    }

    if ($idfolder) {
        $folder = $gwahuddlefrontend->getFolderById($idfolder);
    } else {
        try {
            $folder = $gwahuddlefrontend->getBaseFolder($idfolder);
        } catch (\Exception $e) {
            // error getting workspace
            // either not selected, or not authorized
            return '';
        }
    }

    if ($folder) {
        return $gwahuddlefrontend->getFolderHTML($folder);
    }
}

function gwahuddle_document_HTML( $iddocument ) {
    global $gwahuddlefrontend;
    if ($document = $gwahuddlefrontend->getDocumentById($iddocument)) {
        return $gwahuddlefrontend->getDocumentHTML($document);
    }
}
