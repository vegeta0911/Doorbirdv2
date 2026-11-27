<?php
/*
 * Proxy d'images Doorbird V2
 *
 * Accès : session Jeedom connectée, ou clé du proxy (&key=...) pour les applications / notifications.
 *
 * Deux modes :
 *   - Images enregistrées par le plugin (dossier data/) :
 *       core/php/doorbirdv2.proxy.php?mode=file&type=Appel&file=appel1.png
 *   - Snapshot en direct du portier (identifiants Doorbird jamais exposés au navigateur) :
 *       core/php/doorbirdv2.proxy.php?mode=live&id=<id équipement>
 *   - Flux vidéo MJPEG du portier (video.cgi) :
 *       core/php/doorbirdv2.proxy.php?mode=stream&id=<id équipement>
 *
 * À placer dans : plugins/doorbirdv2/core/php/doorbirdv2.proxy.php
 * À protéger avec : plugins/doorbirdv2/data/.htaccess (Require all denied)
 */

require_once __DIR__ . '/../../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');

function doorbirdv2_proxy_fail($_code, $_log = '') {
    if ($_log !== '') {
        log::add('doorbirdv2', 'debug', '[proxy] ' . $_log);
    }
    http_response_code($_code);
    exit;
}

# Accès : session Jeedom, ou clé du proxy
$proxyKey = (string) config::byKey('proxyKey', 'doorbirdv2');
if (!isConnect() && ($proxyKey === '' || !hash_equals($proxyKey, (string) init('key')))) {
    doorbirdv2_proxy_fail(403);
}

$mode = (string) init('mode', 'file');

/* ------------------------------------------------------------------ */
/* Mode « file » : images enregistrées dans plugins/doorbirdv2/data/  */
/* ------------------------------------------------------------------ */
if ($mode === 'file') {
    # Dossiers autorisés : noms EXACTS (sensible à la casse) des sous-dossiers de data/
    $allowedTypes = array('Appel', 'Move');

    $type = (string) init('type');
    if (!in_array($type, $allowedTypes, true)) {
        doorbirdv2_proxy_fail(400, 'Type non autorisé : ' . $type);
    }

    $base = realpath(__DIR__ . '/../../data/' . $type);
    $path = ($base === false) ? false : realpath($base . DIRECTORY_SEPARATOR . basename((string) init('file')));

    # Empêche de sortir du dossier (../) et refuse les fichiers inexistants
    if ($path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
        doorbirdv2_proxy_fail(404, 'Fichier introuvable : ' . $type . '/' . init('file'));
    }

    $mime = (string) mime_content_type($path);
    if (preg_match('#^image/(png|jpeg|gif|webp)$#', $mime) !== 1) {
        doorbirdv2_proxy_fail(415, 'Type de contenu non autorisé : ' . $mime);
    }

    # Cache : 304 uniquement si l'image n'a réellement pas changé
    $mtime = filemtime($path);
    $size  = filesize($path);
    $etag  = '"' . md5($path . '|' . $mtime . '|' . $size) . '"';

    header('Cache-Control: private, no-cache');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

    $ifNoneMatch     = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : null;
    $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
    if (($ifNoneMatch !== null && $ifNoneMatch === $etag)
        || ($ifNoneMatch === null && $ifModifiedSince !== false && $ifModifiedSince >= $mtime)) {
        http_response_code(304);
        exit;
    }

    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . $size);
    readfile($path);
    exit;
}

/* ------------------------------------------------------------------ */
/* Mode « stream » : flux vidéo MJPEG du portier (video.cgi)          */
/* ------------------------------------------------------------------ */
if ($mode === 'stream') {
    $eqLogic = eqLogic::byId((int) init('id'));
    if (!is_object($eqLogic) || $eqLogic->getEqType_name() !== 'doorbirdv2') {
        doorbirdv2_proxy_fail(404, 'Équipement introuvable : ' . init('id'));
    }

    $addr = trim((string) $eqLogic->getConfiguration('addr'));
    $user = trim((string) $eqLogic->getConfiguration('user'));
    $pass = trim((string) $eqLogic->getConfiguration('pass'));
    if ($addr === '') {
        doorbirdv2_proxy_fail(404, 'Adresse du Doorbird non configurée');
    }
    $url = doorbirdv2::getScheme($eqLogic) . $addr . '/bha-api/video.cgi';

    # INDISPENSABLE : libère la session PHP, sinon toutes les autres requêtes Jeedom
    # de cet utilisateur restent bloquées tant que le flux est ouvert.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    # Durée maximale d'un flux, par sécurité. Le cron10 rafraîchit le widget toutes les 10 min,
    # ce qui ouvre un nouveau flux et ferme l'ancien (détecté par connection_aborted()).
    $maxDuration = 900;
    set_time_limit($maxDuration + 30);
    ignore_user_abort(false);

    # Pas de mise en tampon ni de compression : les images doivent partir tout de suite
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    @ini_set('zlib.output_compression', '0');
    @ini_set('implicit_flush', '1');

    # Hôte du Doorbird : seules les redirections vers ce même hôte sont suivies
    $doorbirdHost = strtolower((string) parse_url($url, PHP_URL_HOST));

    $state = array('started' => false, 'httpCode' => 0, 'contentType' => '', 'location' => '');

    for ($redirects = 0; $redirects <= 3; $redirects++) {
    $state['httpCode'] = 0;
    $state['contentType'] = '';
    $state['location'] = '';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); # redirections suivies à la main, voir plus bas
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $pass);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_TIMEOUT, $maxDuration);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // certificat auto-signé du Doorbird (réseau local)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$state) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m)) {
            $state['httpCode'] = (int) $m[1];
        } elseif (stripos($header, 'Content-Type:') === 0) {
            $state['contentType'] = trim(substr($header, 13));
        } elseif (stripos($header, 'Location:') === 0) {
            $state['location'] = trim(substr($header, 9));
        }
        return strlen($header);
    });

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$state) {
        if (!$state['started']) {
            # Premier bloc reçu : vérifie la réponse du Doorbird avant d'envoyer quoi que ce soit
            if ($state['httpCode'] !== 200
                || preg_match('#^multipart/x-mixed-replace\s*;\s*boundary=[^\s;]+#i', $state['contentType']) !== 1) {
                return 0; # interrompt le transfert
            }
            header('Content-Type: ' . $state['contentType']);
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('X-Accel-Buffering: no'); # nginx
            $state['started'] = true;
        }
        echo $data;
        flush();
        # Le visiteur a fermé la page : on coupe la connexion au Doorbird
        return connection_aborted() ? 0 : strlen($data);
    });

    curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($state['started']) {
        exit; # flux terminé (visiteur parti ou durée maximale atteinte)
    }

    # Redirection (301, 302, 307, 308) : suivie seulement si elle reste sur le Doorbird
    if (in_array($state['httpCode'], array(301, 302, 303, 307, 308), true) && $state['location'] !== '') {
        $location = $state['location'];
        if (strpos($location, '//') === 0) {
            $location = parse_url($url, PHP_URL_SCHEME) . ':' . $location;
        } elseif (!preg_match('#^https?://#i', $location)) {
            # Redirection relative : on garde le schéma, l'hôte et le port actuels
            $port = parse_url($url, PHP_URL_PORT);
            $location = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST)
                . ($port ? ':' . $port : '') . '/' . ltrim($location, '/');
        }
        if (strtolower((string) parse_url($location, PHP_URL_HOST)) !== $doorbirdHost) {
            doorbirdv2_proxy_fail(502, 'Redirection vers un autre hôte refusée : ' . $location);
        }
        log::add('doorbirdv2', 'debug', '[proxy] video.cgi redirigé (HTTP ' . $state['httpCode'] . ') vers ' . $location);
        $url = $location;
        continue;
    }

    doorbirdv2_proxy_fail(502, 'Flux Doorbird indisponible (HTTP ' . $state['httpCode'] . ', ' . $state['contentType']
        . ($state['location'] !== '' ? ', Location: ' . $state['location'] : '')
        . ', cURL ' . $errno . ' ' . $error . ')');
    }

    doorbirdv2_proxy_fail(502, 'Trop de redirections pour video.cgi');
}

doorbirdv2_proxy_fail(400, 'Mode inconnu : ' . $mode);