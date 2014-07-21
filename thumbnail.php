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
require_once __DIR__.'/lib/gwa/huddle/ThumbnailCached.class.php';

$persistance = new \gwa\huddle\Persistance(__DIR__.'/_data/data.json');
$api = new \gwa\huddle\Api(
    'NewsroomGore-tex',
    'http://gwadev.de/HuddleRedirect',
    __DIR__.'/cacert.pem'
);
$user = \gwa\huddle\User::getUserInstance($persistance, $api);

if (!$user->isAuthorized()) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$api->setToken($user->getAccessToken());

\gwa\huddle\ApiCallCached::setApi($api);
\gwa\huddle\ApiCallCached::setCacheDir(__DIR__.'/_cache/api');

// get document
$uri = \gwa\huddle\Document::getDocumentURI($id);
$document = \gwa\huddle\Document::getDocumentInstance($uri);
$thumbnail = new \gwa\huddle\ThumbnailCached($api, __DIR__.'/_cache/thumbnails', $document);
$thumbnail->output();
