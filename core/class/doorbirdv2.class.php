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
      $eqLogics = eqLogic::byType('doorbirdv2', true);
      if($eqLogics[0] == ''){
        log::add('doorbirdv2', 'error', 'Merci de configurer un n\'équipement');
       }  
      else
      {
         doorbirdv2::apirl();
      }
    }
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
            $cmd->setOrder(6);
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
            $cmd->setOrder(5);
        }
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setConfiguration('url','open-door.cgi');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

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
        $cmd->setTemplate("dashboard",'presence');
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
        $cmd->setTemplate("dashboard",'door');
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
			$path_url_live = $cmd->getId();

        $url = network::getNetworkAccess('internal') . '/plugins/doorbirdv2/core/api/jeeDoorbirdv2.php?apikey=' . jeedom::getApiKey('doorbirdv2') . '%26id=' . $this->getId() . '%26sensor=';
        $addr = trim($this->getConfiguration('addr'));
        $user = trim($this->getConfiguration('user'));
        $pass = trim($this->getConfiguration('pass'));
        
    
      
        $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?reset=1&user=' . $user . '&password=' . $pass;
        doorbirdv2::callDoor($urlfinal,$user,$pass);
        sleep(2);
      
        $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?url=' . $url . 'doorbell&subscribe=1&event=doorbell&user=' . $user . '&password=' . $pass;
        doorbirdv2::callDoor($urlfinal,$user,$pass);
        $urlfinal = " ";
        sleep(2);
        
        
        $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?url=' . $url . 'dooropen&subscribe=1&event=dooropen&user=' . $user . '&password=' . $pass;
        doorbirdv2::callDoor($urlfinal,$user,$pass);
        sleep(2);

        $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?url=' . $url . 'motion&subscribe=1&event=motionsensor&user=' . $user . '&password=' . $pass;
        doorbirdv2::callDoor($urlfinal,$user,$pass);
       
        if (class_exists('camera')) {
            $urlfinal = 'http://' . $addr . '/bha-api/video.cgi?sessionid='.doorbirdv2::apirl($api3);
            doorbird::syncCamera($addr,$urlfinal,$user,$pass);
        }
      
       doorbirdv2::apirl();
        
      
        
    }
 
 public function apirl() {  
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
  public function syncCamera($addr,$urlfinal,$user,$pass) {
        $camera = camera::byLogicalId($addr, 'camera');
        if (!is_object($camera)) {
            $camera = new camera();
            $camera->setDisplay('height', '1280');
            $camera->setDisplay('width', '720');
            $camera->setName('Doorbirdv2' . $addr);
            $camera->setConfiguration('ip', $addr);
            $camera->setConfiguration('urlStream',  $urlfinal);
            $camera->setConfiguration('username', $user);
            $camera->setConfiguration('password', $pass);
            $camera->setEqType_name('camera');
            $camera->setConfiguration('protocole', 'http');
            $camera->setConfiguration('device', ' ');
            $camera->setConfiguration('applyDevice', ' ');
            $camera->setConfiguration('port', '80');
            $camera->setLogicalId($addr);
            $camera->save();
        }
  }
    public function postRemove() {
        $url = network::getNetworkAccess('internal') . '/plugins/doorbirdv2/core/api/jeeDoorbirdv2.php?apikey=' . config::byKey('api') . '&id=' . $this->getId() . '&sensor=';
        $addr = trim($this->getConfiguration('addr'));
        $user = trim($this->getConfiguration('user'));
        $pass = trim($this->getConfiguration('pass'));
        
        $urlfinal = 'http://' . $addr . '/bha-api/notification.cgi?reset=1&user=' . $user . '&password=' . $pass;
        doorbirdv2::callDoor($urlfinal,$user,$pass);
    }

    public function callDoor($url,$user,$pass) {
        $curl = curl_init();
        
        log::add('doorbirdv2', 'debug', 'Appel : ' . $url . ' avec ' . $user . ':' . $pass);

        $auth = base64_encode($user . ':' . $pass);
        $header = array("Authorization: Basic $auth");
        $opts = array( 'http' => array ('method'=>'GET',
        'header'=>$header));
        $ctx = stream_context_create($opts);
        $retour = file_get_contents($url,false,$ctx);
        $cible = explode('/',$url);
        
        log::add('doorbirdv2', 'debug', 'Retour : ' . $retour);
    }
}

class doorbirdv2Cmd extends cmd {
    public function execute($_options = null) {
        switch ($this->getType()) {
            case 'info' :
            return $this->getConfiguration('value');
            break;
            case 'action' :
            $request = $this->getConfiguration('request');
            switch ($this->getSubType()) {
                case 'slider':
                $request = str_replace('#slider#', $value, $request);
                break;
                case 'color':
                $request = str_replace('#color#', $_options['color'], $request);
                break;
                case 'message':
                if ($_options != null)  {
                    $replace = array('#title#', '#message#');
                    $replaceBy = array($_options['title'], $_options['message']);
                    if ( $_options['title'] == '') {
                        throw new Exception(__('Le sujet ne peuvent être vide', __FILE__));
                    }
                    $request = str_replace($replace, $replaceBy, $request);
                }
                else
                $request = 1;
                break;
                default : $request == null ?  1 : $request;
            }

            $eqLogic = $this->getEqLogic();
            $addr = trim($eqLogic->getConfiguration('addr'));
            $url = $this->getConfiguration('url');
            $user = trim($eqLogic->getConfiguration('user'));
            $pass = trim($eqLogic->getConfiguration('pass'));
            $urlfinal = 'http://' . $addr . '/bha-api/' . $url;
            doorbirdv2::callDoor($urlfinal,$user,$pass);
            
            
            return true;
        }
        return true;
      
    }
}

?>
