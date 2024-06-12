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
    public static function cron10(){
     doorbirdv2::apirl();
    }
    public static function cron(){
        doorbirdv2::doorcamov();
        doorbirdv2::doorappel();
    }
    public function preUpdate() {
        if ($this->getConfiguration('addr') == '') {
            throw new Exception(__('L\'adresse ne peut être vide',__FILE__));
        }
        $info = $this->callDoor('info.cgi');
        $this->setConfiguration('firmware',$info['BHA']['VERSION'][0]['FIRMWARE']);
        $this->setConfiguration('build',$info['BHA']['VERSION'][0]['BUILD_NUMBER']);
        $this->setConfiguration('type',$info['BHA']['VERSION'][0]['DEVICE-TYPE']);
        $this->setConfiguration('mac',$info['BHA']['VERSION'][0]['WIFI_MAC_ADDR']);
        $this->setConfiguration('relai',json_encode($info['BHA']['VERSION'][0]['RELAYS']),true);
    }

    public function preSave() {
        $this->setLogicalId($this->getConfiguration('addr'));
        
    }


    public function postUpdate() {
       $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(),'light');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setLogicalId('light');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Lumière', __FILE__));
            $cmd->setOrder(7);
            
        }
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setConfiguration('url','light-on.cgi');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(),'door');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setLogicalId('door');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Ouverture Porte', __FILE__));
            $cmd->setOrder(6);
            
        }
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setConfiguration('url','open-door.cgi?r=1');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
        
        
        if (strpos($this->getConfiguration('type'),'D21')) {
            $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(),'door2');
            if (!is_object($cmd)) {
                $cmd = new doorbirdv2Cmd();
                $cmd->setLogicalId('door2');
                $cmd->setIsVisible(1);
                $cmd->setName(__('Ouverture Porte 2', __FILE__));
                $cmd->setOrder(5);
            }
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setConfiguration('url','open-door.cgi?r=2');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
        }

        $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(),'doorbell');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setLogicalId('doorbell');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Sonnerie', __FILE__));
            $cmd->setOrder(4);
            
        }
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setDisplay('generic_type','PRESENCE');
        $cmd->setConfiguration('returnStateValue',0);
        $cmd->setConfiguration('returnStateTime',1);
        $cmd->setConfiguration('repeatEventManagement','always');
        $cmd->setTemplate("mobile",'alert');
        $cmd->setTemplate("dashboard",'alert' );
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(),'motion');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setLogicalId('motion');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Mouvement', __FILE__));
            $cmd->setOrder(3);
            
        }
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setDisplay('generic_type','PRESENCE');
        $cmd->setConfiguration('returnStateValue',0);
        $cmd->setConfiguration('returnStateTime',1);
        $cmd->setConfiguration('repeatEventManagement','always');
        $cmd->setTemplate("mobile",'presence');
        $cmd->setTemplate("dashboard",'presence' );
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(),'dooropen');
        if (!is_object($cmd)) {
            $cmd = new doorbirdv2Cmd();
            $cmd->setLogicalId('dooropen');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Porte', __FILE__));
            $cmd->setOrder(2);
            
        }
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setDisplay('generic_type','LOCK_STATE');
        $cmd->setConfiguration('returnStateValue',1);
        $cmd->setConfiguration('returnStateTime',1);
        $cmd->setConfiguration('repeatEventManagement','always');
        $cmd->setTemplate("mobile",'door');
        $cmd->setTemplate("dashboard",'door' );
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
  
	    $cmd = $this->getCmd(null, 'path_url_live');
		if (!is_object($cmd)) {
			$cmd = new doorbirdv2Cmd();
                        $cmd->setEqLogic_id($this->getId());
		        $cmd->setLogicalId('path_url_live');
			$cmd->setName(__('Camera Doorbird', __FILE__));
                        $cmd->setOrder(1);
			
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
                        $cmd->setOrder(13);
			
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
                        $cmd->setOrder(14);
			
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
			$cmd->save();
      
        $url = network::getNetworkAccess('internal') . '/plugins/doorbirdv2/core/api/jeeDoorbirdv2.php?apikey=' . jeedom::getApiKey('doorbirdv2') . '%26id=' . $this->getId() . '%26sensor=';
        $this->callDoor('notification.cgi?reset=1');
        sleep(2);
        $this->callDoor('notification.cgi?url=' . $url . 'doorbell&subscribe=1&event=doorbell');
        sleep(2);
        $this->callDoor('notification.cgi?url=' . $url . 'dooropen&subscribe=1&event=dooropen');
        sleep(2);
        $this->callDoor('notification.cgi?url=' . $url . 'motion&subscribe=1&event=motionsensor');
        if (class_exists('camera')) {
            doorbirdv2::syncCamera($this->getConfiguration('addr'),$this->getConfiguration('user'),$this->getConfiguration('pass'));
        }
            doorbirdv2::apirl();   
    }
 
    public static function apirl() {  
      foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {
            
            $addr = trim($eqLogic->getConfiguration('addr'));
            $user = trim($eqLogic->getConfiguration('user'));
            $pass = trim($eqLogic->getConfiguration('pass'));
            $urlid = 'http://' . $addr . '/bha-api/getsession.cgi?http-user='.$user.'&http-password='.$pass;
            $retours = file_get_contents($urlid,false);
            $api = explode("  ", $retours);
            $api1 = explode(":", $api[0]);
            $api3 = substr($api1[3], 2, 61);
        }
          log::add('doorbirdv2', 'debug', 'Camera SESSIONID : ' . $api3 . ' avec ' . $user . ':' . $pass);
          doorbirdv2::doorcam($api3,$addr);     
    } 
  
    public static function doorcam($api,$ip) {
        foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {
            $urlLive = 'http://' . $ip . '/bha-api/video.cgi?sessionid='.$api;
            $form = '<img style="display: block;-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms;" src='.$urlLive . ' width="324" height="243">';
            
            log::add('doorbirdv2', 'debug', 'Camera api : ' . $api . ' avec ' . $urlLive . ':' . $pass);
            $eqLogic->checkAndUpdateCmd('path_url_live', $form);
            $eqLogic->refreshWidget();
        }
    }
    
    public static function doorappel() {
        foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {
            $urlLive = 'http://' . trim($eqLogic->getConfiguration('addr')) . '/bha-api/history.cgi?http-user='.trim($eqLogic->getConfiguration('user')).'&http-password='.trim($eqLogic->getConfiguration('pass')).'&index=1';
            
            if (!file_exists(dirname(__FILE__) . '/../../data/Appel')) {
	       mkdir(dirname(__FILE__) . '/../../data/Appel', 0777, true);
	    }
             
                $img = '/var/www/html/plugins/doorbirdv2/data/Appel/appel.png';
                $accesimg = 'plugins/doorbirdv2/data/Appel/appel.png';
                $form = '<img style="display: block;-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms;" src='.$accesimg . ' width="324" height="243">';
                file_put_contents($img, file_get_contents($urlLive));
                $ch = curl_init($urlLive);
                $fp = fopen($img, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_exec($ch);
                fclose($fp);
            
            log::add('doorbirdv2', 'debug', 'ImageAppel api : '. $urlLive);
            $eqLogic->checkAndUpdateCmd('imageappel', $form);
            $eqLogic->refreshWidget();
        }
    }

    public static function doorcamov() {
        foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {
            if (!file_exists(dirname(__FILE__) . '/../../data/Move')) {
	        mkdir(dirname(__FILE__) . '/../../data/Move', 0777, true);
	    }

                $urlLive = 'http://' . trim($eqLogic->getConfiguration('addr')) . '/bha-api/history.cgi?http-user='.trim($eqLogic->getConfiguration('user')).'&http-password='.trim($eqLogic->getConfiguration('pass')).'&event=motionsensor&index=1';
                $img = '/var/www/html/plugins/doorbirdv2/data/Move/mov.png';
                file_put_contents($img, file_get_contents($urlLive));
                $ch = curl_init($urlLive);
                $fp = fopen($img, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_exec($ch);
                fclose($fp);
                $accesimg = 'plugins/doorbirdv2/data/Move/mov.png';
                $form = '<img style="display: block;-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms;" src='.$accesimg . ' width="324" height="243">';
                
            
            log::add('doorbirdv2', 'debug', 'ImageMov api : '. $urlLive);
            $eqLogic->checkAndUpdateCmd('imagemov', $form);
            $eqLogic->refreshWidget();
        }
    }

    public function syncCamera($_addr,$_user,$_pass) {
        $urlfinal = 'http://' . $_addr . '/bha-api/video.cgi?http-user=#user#&http-password=#password#';
        $camera = camera::byLogicalId($_addr, 'camera');
        if (!is_object($camera)) {
            $camera = new camera();
            $camera->setDisplay('height', '1280');
            $camera->setDisplay('width', '720');
            $camera->setName('Doorbird ' . $_addr);
            $camera->setConfiguration('ip', $_addr);
            $camera->setConfiguration('urlStream',  '/bha-api/image.cgi');
            $camera->setConfiguration('cameraStreamAccessUrl',  'http://#user#:#password#@' . $_addr . '/axis-cgi/mjpg/video.cgi');
            $camera->setConfiguration('username', $_user);
            $camera->setConfiguration('password', $_pass);
            $camera->setEqType_name('camera');
            $camera->setConfiguration('protocole', 'http');
            $camera->setConfiguration('device', ' ');
            $camera->setConfiguration('applyDevice', ' ');
            $camera->setConfiguration('port', '80');
            $camera->setLogicalId($_addr);
            $camera->save();
        }
    }
    public function postRemove() {
        $this->callDoor('notification.cgi?reset=1');
    }

    public function callDoor($_uri) {
        if ($this->getConfiguration('addr') == '') {
            exit;
        }
        $auth = base64_encode(trim($this->getConfiguration('user')) . ':' . trim($this->getConfiguration('pass')));
        $request_http = new com_http('https://' . trim($this->getConfiguration('addr')) . '/bha-api/' . $_uri);
        $request_http->setHeader(array("Authorization: Basic $auth"));
        $retour = json_decode($request_http->exec(30),true);
    
        log::add('doorbirdv2', 'debug', 'Appel : ' . $_uri . ', Retour : ' . json_encode($retour),true);
        return $retour;
      
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

?>
