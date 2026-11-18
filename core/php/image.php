<?php
require_once __DIR__ . '/../../../../core/php/core.inc.php';

// Accès autorisé si l'utilisateur est connecté à Jeedom, ou avec la clé API du plugin
include_file('core', 'authentification', 'php');
if (!isConnect() && !jeedom::apiAccess(init('apikey'), 'doorbirdv2')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$type = init('type');
if (!in_array($type, array('Appel', 'Move', 'Call'), true)) { // adapter aux dossiers réels
    http_response_code(400);
    die();
}

$base = realpath(__DIR__ . '/../../data/' . $type);
$path = realpath($base . '/' . basename(init('file')));

// Empêche de sortir du dossier (../) et refuse les fichiers inexistants
if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
    http_response_code(404);
    die();
}

$mime = mime_content_type($path);
if (!in_array($mime, array('image/png', 'image/jpeg'), true)) {
    http_response_code(415);
    die();
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-cache, no-store, must-revalidate');
readfile($path);
