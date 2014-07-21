<?php
if (!isset($_GET['id']) || !isset($_GET['v'])) {
    header('HTTP/1.0 404 Not Found');
    exit;
}
$id = $_GET['id'];
$version = $_GET['v'];
$pattern = '/^\d+$/';
if (!preg_match($pattern, $id) || !preg_match($pattern, $version)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

require_once __DIR__.'/lib/gwa/huddle/Api.class.php';
require_once __DIR__.'/lib/gwa/huddle/ApiCallCached.class.php';
require_once __DIR__.'/lib/gwa/huddle/Persistance.class.php';
require_once __DIR__.'/lib/gwa/huddle/User.class.php';
require_once __DIR__.'/lib/gwa/huddle/Document.class.php';

$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/data.json');
$config = include(__DIR__.'config.php');
$gwahuddleapi = new \gwa\huddle\Api(
    $config['clientid'],
    $config['redirecturi'],
    __DIR__.'/cacert.pem'
);
$user = \gwa\huddle\User::getUserInstance($persistance, $api);

if (!$user->isAuthorized()) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$api->setToken($user->getAccessToken());

\gwa\huddle\ApiCallCached::setApi($api);
\gwa\huddle\ApiCallCached::setCacheDir(__DIR__.'/_cache/api');

// get document
$uri =  \gwa\huddle\Document::getDocumentURI($id);
$document = \gwa\huddle\Document::getDocumentInstance($uri);

// force download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$document->getFileName().'"'); //<<< Note the " " surrounding the file name
header('Content-Transfer-Encoding: binary');
header('Connection: Keep-Alive');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
echo $api->fetch($document->getFileURI());
