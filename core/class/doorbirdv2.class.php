<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class doorbirdv2 extends eqLogic {

    /* Image affichée quand aucune image n'est disponible.
     * Hors du dossier data/ (bloqué par data/.htaccess) : déplacer data/no_image.png ici. */
    const NO_IMAGE = '/plugins/doorbirdv2/data/no_image.png';

    /* URL du proxy d'images (session Jeedom ou &key=) */
    const PROXY = '/plugins/doorbirdv2/core/php/doorbirdv2.proxy.php';

    public static function cron() {
        doorbirdv2::doorcamov();
        doorbirdv2::doorappel();
    }

    public static function cron10() {
        $eqLogics = eqLogic::byType('doorbirdv2', true);
        if (count($eqLogics) == 0) {
            log::add('doorbirdv2', 'error', 'Merci de configurer un équipement');
        } else {
            doorbirdv2::apirl();
        }
    }

    public function preUpdate() {
        if ($this->getConfiguration('addr') == '') {
            throw new Exception(__('L\'adresse ne peut être vide', __FILE__));
        }
        if ($this->getConfiguration('firmware') == '') {
            $info = $this->callDoor('info.cgi');
            if (is_array($info) && isset($info['BHA']['VERSION'][0])) {
                $version = $info['BHA']['VERSION'][0];
                $this->setConfiguration('firmware', $version['FIRMWARE'] ?? '');
                $this->setConfiguration('build', $version['BUILD_NUMBER'] ?? '');
                $this->setConfiguration('type', $version['DEVICE-TYPE'] ?? '');
                $this->setConfiguration('mac', $version['WIFI_MAC_ADDR'] ?? '');
                $this->setConfiguration('relai', json_encode($version['RELAYS'] ?? array()), true);
            }
        }
    }

    public function preSave() {
        $this->setLogicalId($this->getConfiguration('addr'));
    }

    public function postUpdate() {

        $relais = $this->getConfiguration('relai');
        log::add('doorbirdv2', 'debug', 'Relais : ' . (is_array($relais) ? json_encode($relais) : $relais));

        $data = is_array($relais) ? $relais : json_decode((string) $relais, true);
        if (!is_array($data)) {
            $data = array();
        }

        foreach ($data as $relai) {
            $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(), 'door' . $relai);
            if (!is_object($cmd)) {
                $cmd = new doorbirdv2Cmd();
                $cmd->setLogicalId('door' . $relai);
                $cmd->setIsVisible(1);
                $cmd->setName(__('Ouverture Porte ', __FILE__) . $relai);
            }
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setConfiguration('url', 'open-door.cgi?r=' . $relai);
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
        }

        $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(), 'light');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setLogicalId('light');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Lumière', __FILE__));
        }
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setConfiguration('url', 'light-on.cgi');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(), 'doorbell');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setLogicalId('doorbell');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Sonnerie', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setDisplay('generic_type', 'PRESENCE');
        $cmd->setConfiguration('returnStateValue', 0);
        $cmd->setConfiguration('returnStateTime', 1);
        $cmd->setConfiguration('repeatEventManagement', 'always');
        $cmd->setTemplate("mobile", 'alert');
        $cmd->setTemplate("dashboard", 'alert');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(), 'motion');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setLogicalId('motion');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Mouvement', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setDisplay('generic_type', 'PRESENCE');
        $cmd->setConfiguration('returnStateValue', 0);
        $cmd->setConfiguration('returnStateTime', 1);
        $cmd->setConfiguration('repeatEventManagement', 'always');
        $cmd->setTemplate("mobile", 'presence');
        $cmd->setTemplate("dashboard", 'presence');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(), 'dooropen');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setLogicalId('dooropen');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Porte', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setDisplay('generic_type', 'LOCK_STATE');
        $cmd->setConfiguration('returnStateValue', 1);
        $cmd->setConfiguration('returnStateTime', 1);
        $cmd->setConfiguration('repeatEventManagement', 'always');
        $cmd->setTemplate("mobile", 'door');
        $cmd->setTemplate("dashboard", 'door');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = $this->getCmd(null, 'path_url_live');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('path_url_live');
            $cmd->setName(__('Camera Doorbird', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setIsVisible(1);
        $cmd->save();

        $cmd = $this->getCmd(null, 'imagemov');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('imagemov');
            $cmd->setName(__('Image Mouvements', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setIsVisible(1);
        $cmd->save();

        $cmd = $this->getCmd(null, 'imageappel');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId('imageappel');
            $cmd->setName(__('Image Appel', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setIsVisible(1);
        $cmd->save();

        $url = network::getNetworkAccess('internal') . '/plugins/doorbirdv2/core/api/jeeDoorbirdv2.php?apikey=' . jeedom::getApiKey('doorbirdv2') . '%26id=' . $this->getId() . '%26sensor=';
        $this->callDoor('notification.cgi?reset=1');
        sleep(1);
        $this->callDoor('notification.cgi?url=' . $url . 'doorbell&subscribe=1&event=doorbell');
        sleep(1);
        $this->callDoor('notification.cgi?url=' . $url . 'dooropen&subscribe=1&event=dooropen');
        sleep(1);
        $this->callDoor('notification.cgi?url=' . $url . 'motion&subscribe=1&event=motionsensor');
        doorbirdv2::apirl();
    }

    public static function getProxyKey() {
        $key = config::byKey('proxyKey', 'doorbirdv2');
        if ($key == '') {
            $key = config::genKey(32);
            config::save('proxyKey', $key, 'doorbirdv2');
        }
        return $key;
    }

    public static function isHttpsAvailable($host) {
        $ch = curl_init('https://' . $host . '/');
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // certificat auto-signé du Doorbird (réseau local)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);
        $https_available = curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 0;
        curl_close($ch);
        return $https_available;
    }

    /* "https://" ou "http://" pour cet équipement, mémorisé 1 h pour ne pas tester à chaque minute */
    public static function getScheme($eqLogic) {
        $cacheKey = 'doorbirdv2::scheme::' . $eqLogic->getId();
        $scheme = cache::byKey($cacheKey)->getValue('');
        if ($scheme == '') {
            $scheme = doorbirdv2::isHttpsAvailable(trim($eqLogic->getConfiguration('addr'))) ? 'https://' : 'http://';
            cache::set($cacheKey, $scheme, 3600);
            log::add('doorbirdv2', 'info', $eqLogic->getHumanName() . ' : ' . ($scheme == 'https://' ? 'HTTPS disponible' : 'HTTPS non disponible, HTTP utilisé'));
        }
        return $scheme;
    }

    /* Télécharge une image de l'historique Doorbird dans data/<dossier>/<fichier>.
     * Une seule requête, identifiants en authentification Basic (pas dans l'URL).
     * Retourne true si une image valide a été enregistrée. */
    public static function downloadHistoryImage($eqLogic, $query, $dir, $file) {
        $addr = trim($eqLogic->getConfiguration('addr'));
        if ($addr == '') {
            return false;
        }
        $url = doorbirdv2::getScheme($eqLogic) . $addr . '/bha-api/history.cgi?' . $query;

        $folder = __DIR__ . '/../../data/' . $dir;
        if (!file_exists($folder)) {
            mkdir($folder, 0775, true);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, trim($eqLogic->getConfiguration('user')) . ':' . trim($eqLogic->getConfiguration('pass')));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // certificat auto-signé du Doorbird (réseau local)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($body === false || $httpCode !== 200 || stripos($contentType, 'image/') !== 0) {
            log::add('doorbirdv2', 'debug', $eqLogic->getHumanName() . ' : pas d\'image (' . $dir . ', HTTP ' . $httpCode . ', ' . $contentType . ')');
            return false;
        }

        # Écriture atomique : le proxy ne sert jamais une image à moitié écrite
        $tmp = $folder . '/.' . $file . '.tmp';
        if (file_put_contents($tmp, $body) === false) {
            return false;
        }
        rename($tmp, $folder . '/' . $file);
        return true;
    }

    public static function imageHtml($src, $noImage = false) {
        if ($noImage) {
            return '<img style="display: block;-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms;" src="' . $src . '" width="324" height="243">';
        }
        return '<img style="display: block; margin: auto; cursor: zoom-in; max-width: 88%; height: auto;" src="' . $src . '">';
    }

    public static function apirl() {
        doorbirdv2::doorcam();
    }

    /* Caméra : flux MJPEG video.cgi relayé par le proxy.
     * Ni identifiants, ni sessionid, ni clé dans le HTML du widget : la session Jeedom suffit.
     * Le cron10 rafraîchit le widget (nouveau flux toutes les 10 min) ;
     * si le flux ne démarre pas (Doorbird occupé, réseau), le navigateur réessaie après 5 s. */
    public static function doorcam() {
        foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {
            $src = doorbirdv2::PROXY . '?mode=stream&id=' . $eqLogic->getId();
            $form = '<img style="display: block; margin: auto; cursor: zoom-in; max-width: 88%; height: auto;"'
                . ' src="' . $src . '&t=' . time() . '"'
                . ' onerror="var i=this;setTimeout(function(){i.src=\'' . $src . '&t=\'+Date.now();},5000);">';
            log::add('doorbirdv2', 'debug', $eqLogic->getHumanName() . ' : caméra via ' . $src);
            $eqLogic->checkAndUpdateCmd('path_url_live', $form);
            $eqLogic->refreshWidget();
        }
    }

    public static function doorappel() {
        foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {
            if (doorbirdv2::downloadHistoryImage($eqLogic, 'index=1', 'Appel', 'appel1.png')) {
                $form = doorbirdv2::imageHtml(doorbirdv2::PROXY . '?mode=file&type=Appel&file=appel1.png&t=' . time());
                log::add('doorbirdv2', 'debug', $eqLogic->getHumanName() . ' : image d\'appel mise à jour');
            } else {
                $form = doorbirdv2::imageHtml(doorbirdv2::NO_IMAGE, true);
            }
            $eqLogic->checkAndUpdateCmd('imageappel', $form);
            $eqLogic->refreshWidget();
        }
    }

    public static function doorcamov() {
        foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {
            if (doorbirdv2::downloadHistoryImage($eqLogic, 'event=motionsensor&index=1', 'Move', 'mov1.png')) {
                $form = doorbirdv2::imageHtml(doorbirdv2::PROXY . '?mode=file&type=Move&file=mov1.png&t=' . time());
                log::add('doorbirdv2', 'debug', $eqLogic->getHumanName() . ' : image de mouvement mise à jour');
            } else {
                $form = doorbirdv2::imageHtml(doorbirdv2::NO_IMAGE, true);
            }
            $eqLogic->checkAndUpdateCmd('imagemov', $form);
            $eqLogic->refreshWidget();
        }
    }

    public function postRemove() {
        $this->callDoor('notification.cgi?reset=1');
    }

    public function callDoor($_uri) {
        if ($this->getConfiguration('addr') == '') {
            return null;
        }
        $auth = base64_encode(trim($this->getConfiguration('user')) . ':' . trim($this->getConfiguration('pass')));
        $request_http = new com_http('https://' . trim($this->getConfiguration('addr')) . '/bha-api/' . $_uri);
        $request_http->setHeader(array("Authorization: Basic $auth"));
        return json_decode($request_http->exec(30), true);
    }
}


class doorbirdv2Cmd extends cmd {
    public function execute($_options = null) {
        if ($this->getType() == 'action') {
            $eqLogic = $this->getEqLogic();
            $eqLogic->callDoor($this->getConfiguration('url'));
        }
        return true;
    }
}
