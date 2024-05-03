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
   
    public function preUpdate() {
        if ($this->getConfiguration('addr') == '') {
            throw new Exception(__('L\'adresse ne peut être vide',__FILE__));
        }
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
            $cmd->setOrder(2);
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
            $cmd->setOrder(3);
        }
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setConfiguration('url','open-door.cgi');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
        
        /*if (strpos($this->getConfiguration('type'),'D21')) {
            $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(),'door2');
            if (!is_object($cmd)) {
                $cmd = new doorbirdv2Cmd();
                $cmd->setLogicalId('door2');
                $cmd->setIsVisible(1);
                $cmd->setName(__('Ouverture Relais 2', __FILE__));
            }
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setConfiguration('url','open-door.cgi?r=2');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
        }*/

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
            $cmd->setOrder(5);
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
            $cmd->setOrder(6);
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
        
        $cmd = $this->getCmd(null, 'firmware');
		if (!is_object($cmd)) {
			$cmd = new doorbirdv2Cmd();
            $cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('firmware');
		    $cmd->setName(__('FIRMWARE:', __FILE__));
			$cmd->setOrder(7);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
            $cmd->setConfiguration('firmware', $info[0]);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
			$cmd->save();
       
        $cmd = $this->getCmd(null, 'build_number');
		if (!is_object($cmd)) {
			$cmd = new doorbirdv2Cmd();
            $cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('build_number');
		    $cmd->setName(__('BUILD_NUMBER:', __FILE__));
			$cmd->setOrder(8);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
			$cmd->save();
        
        $cmd = $this->getCmd(null, 'mac_addr');
		if (!is_object($cmd)) {
			$cmd = new doorbirdv2Cmd();
            $cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('mac_addr');
		    $cmd->setName(__('MAC_ADDR:', __FILE__));
			$cmd->setOrder(9);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
			$cmd->save();
      
        $cmd = $this->getCmd(null, 'relay');
		if (!is_object($cmd)) {
			$cmd = new doorbirdv2Cmd();
            $cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('relay');
		    $cmd->setName(__('RELAY:', __FILE__));
			$cmd->setOrder(10);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
			$cmd->save();
      
        $cmd = $this->getCmd(null, 'device-type');
		if (!is_object($cmd)) {
			$cmd = new doorbirdv2Cmd();
            $cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('device-type');
		    $cmd->setName(__('DEVICE-TYPE:', __FILE__));
			$cmd->setOrder(11);
			}
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible(1);
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
			//$path_url_live = $cmd->getId();
        
      
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
            doorbirdv2::callDoor('info.cgi');
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
        
            $urlLive = 'http://' . $addr . '/bha-api/video.cgi?sessionid='.$api3;
            $form = '<img style="display: block;-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms;" src='.$urlLive . ' width="324" height="243">';
            
            $eqLogic->checkAndUpdateCmd('path_url_live', $form);
            $eqLogic->refreshWidget();
        }
          log::add('doorbirdv2', 'debug', 'Camera SESSIONID : ' . $urlLive . ' avec ' . $user . ':' . $pass);
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
        $retour = $request_http->exec(30);
        if($_uri == "info.cgi"){
          
          $retour1 = explode('[{', $retour);
          $retour2 = explode(',', $retour1[1]);
         
          $info = '';
          $info = array(0 => substr($retour2[0],12,-1),
                        1 => substr($retour2[1],17,-1),
                        2 => substr($retour2[2],18,-1),
                        3 => substr($retour2[3],11,-2),
                        4 => substr($retour2[4],16,-5));
         
          $this->checkAndUpdateCmd('firmware', $info[0]);
          $this->checkAndUpdateCmd('build_number', $info[1]);
          $this->checkAndUpdateCmd('mac_addr', $info[2]);
          $this->checkAndUpdateCmd('relay', $info[3]);
          $this->checkAndUpdateCmd('device-type', $info[4]);
          $this->refreshWidget();
          
          log::add('doorbirdv2', 'debug', 'Appel : ' . print_r($info, true));
         
          return $info;
        }
        log::add('doorbirdv2', 'debug', 'Appel : ' . $_uri . ', Retour : ' . $retour);
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
