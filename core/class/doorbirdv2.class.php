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
    public static function cron(){
        doorbirdv2::doorcamov();
        doorbirdv2::doorappel();
        
    }
    public static function cron5(){
        doorbirdv2::doorappel2();
        doorbirdv2::doorcamov2();
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

       $relais = $this->getConfiguration('relai');
       log::add('doorbirdv2', 'debug', 'Relais : '. $relais );
       
       $jsonData = $relais;  
       $data = json_decode($jsonData, true);  

       $index = 0;

        while ($index < count($data)) {
           
            $cmd = doorbirdv2Cmd::byEqLogicIdAndLogicalId($this->getId(),'door'.$data[$index]);
            if (!is_object($cmd)) {
                $cmd = new doorbirdv2Cmd();
                $cmd->setLogicalId('door'.$data[$index]);
                $cmd->setIsVisible(1);
                $cmd->setName(__('Ouverture Porte '.$data[$index], __FILE__));
                $cmd->setOrder(5);
            }
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setConfiguration('url','open-door.cgi?r='.$data[$index]);
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
            $index++;  // Passer à l'élément suivant
        } 

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
        $cmd->setTemplate('dashboard', 'doorbirv2::moveimage');
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
        $cmd->setTemplate('dashboard', 'doorbirv2::appelimage');
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
         if (!file_exists(dirname(__FILE__) . '/../../core/api')) {
	            mkdir(dirname(__FILE__) . '/../../core/api', 0777, true);
	        }
     
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
            $form = '<img style="display: block; margin: auto; cursor: zoom-in; max-width: 88%; height: auto;" src='.$urlLive . '>';
            
            log::add('doorbirdv2', 'debug', 'Camera api : ' . $api . ' avec ' . $urlLive);
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
                
                function get_http_response_appel($urlLive) {
                    $headers = get_headers($urlLive);
                    return substr($headers[0], 9, 3);
                }
                if(get_http_response_appel($urlLive) != "200"){
                    $accesimg = 'plugins/doorbirdv2/data/no_image.png';
                    $destaccesimg = 'plugins/doorbirdv2/data/Appel/appel1png';
                    copy($accesimg, $destaccesimg);
                    $form = '<img style="display: block;-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms;" src='.$accesimg . ' width="324" height="243">';
                    $eqLogic->checkAndUpdateCmd('imageappel', $form);
                    $eqLogic->refreshWidget();
                    log::add('doorbirdv2', 'debug', 'ImageAppel pas d&apos;image trouvé : '. $urlLive );
                }
                else
                {
                $img = '/var/www/html/plugins/doorbirdv2/data/Appel/appel1.png';
                $accesimg = 'plugins/doorbirdv2/data/Appel/appel1.png';
                file_put_contents($img, file_get_contents($urlLive));
                $ch = curl_init($urlLive);
                $fp = fopen($img, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_exec($ch);
                fclose($fp);
               
                log::add('doorbirdv2', 'debug', 'doorappel : '. $urlLive);
                }
         }   
      
    }
    
    public static function doorappel2() {
    foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {}
        $outputDir = dirname(__FILE__).'/../../data/Appel/';
        $startIndex = 2;
        $endIndex = 54;
        $minSize = 196;

        for ($i =  $startIndex; $i <= $endIndex; $i++) {
            $fichier = $outputDir .'appel'. $i . '.png';

            if (file_exists($fichier)) {
                unlink($fichier);
            }
        }

        
        $getFileSize = function ($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        $contentLength = 0;
        $headers = explode("\r\n", $response);
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Length:') === 0) {
                $contentLength = (int) trim(substr($header, 15));
                break;
            }
        }

        curl_close($ch);
        return $contentLength > 0 ? $contentLength : false;
    };
        
        for ($index = $startIndex; $index <= $endIndex; $index++) {

        $downloadFile = function ($url, $savePath) {
        $ch = curl_init($url);
        $fp = fopen($savePath, 'wb');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        curl_exec($ch);
        fclose($fp);
        curl_close($ch);
        };

        $urlLive = 'http://' . trim($eqLogic->getConfiguration('addr')) 
                 . '/bha-api/history.cgi?http-user=' . trim($eqLogic->getConfiguration('user')) 
                 . '&http-password=' . trim($eqLogic->getConfiguration('pass')) 
                 . '&index=' . $index;

        $fileSize = $getFileSize($urlLive);
        if ($fileSize === false) {
           log::add('doorbirdv2', 'debug', 'Impossible de déterminer la taille du fichier à l URL : '.$urlLive);
            continue;
        }

        if ($fileSize < $minSize) {
            log::add('doorbirdv2', 'debug', 'Fichier ignoré :'. $urlLive .'(taille = '. $fileSize .' bytes, inférieure à '. $minSize .' bytes)');
            break;
        }

        $savePath = $outputDir . 'appel'.$index.'.png';
        log::add('doorbirdv2', 'debug', 'Téléchargement du fichier depuis '. $urlLive .' (taille = '. $fileSize .'bytes)...');
        $downloadFile($urlLive, $savePath);
        log::add('doorbirdv2', 'info', 'Fichier Appel sauvegardé sous '. $savePath);
        }

        try {

            $fichiers = scandir($outputDir);
            $nombreFichiers = count($fichiers);
                file_put_contents(dirname(__FILE__) . '/../../data/countAppel.php', json_encode([
                    'status' => 'success',
                    'count' => ($nombreFichiers-3),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (Exception $e) {
                    $e->getMessage();
            }
    }
	
    public static function doorcamov() {
        foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {
          $urlLive = 'http://' . trim($eqLogic->getConfiguration('addr')) . '/bha-api/history.cgi?http-user='.trim($eqLogic->getConfiguration('user')).'&http-password='.trim($eqLogic->getConfiguration('pass')).'&event=motionsensor&index=1';
            if (!file_exists(dirname(__FILE__) . '/../../data/Move')) {
	        mkdir(dirname(__FILE__) . '/../../data/Move', 0777, true);
	    }
                function get_http_response_move($urlLive) {
                    $headers = get_headers($urlLive);
                    return substr($headers[0], 9, 3);
                }
                if(get_http_response_move($urlLive) != "200"){
                    $accesimg = 'plugins/doorbirdv2/data/no_image.png';
                    $form = '<img style="display: block;-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms;" src='.$accesimg . ' width="324" height="243">';
                    $eqLogic->checkAndUpdateCmd('imagemov', $form);
                    $eqLogic->refreshWidget();
                     
                }
                else
                {
                $img = '/var/www/html/plugins/doorbirdv2/data/Move/mov1.png';
                $accesimg = 'plugins/doorbirdv2/data/Move/mov1.png';
                file_put_contents($img, file_get_contents($urlLive));
                $ch = curl_init($urlLive);
                $fp = fopen($img, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_exec($ch);
                fclose($fp);
               
                log::add('doorbirdv2', 'debug', 'ImageMov api : '. $urlLive);
                $form = '<img style="display: block;-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms;" src='.$accesimg . ' width="324" height="243">';
                }
        }
    }

    public static function doorcamov2() {
        foreach (eqLogic::byType('doorbirdv2', true) as $eqLogic) {}
        $outputDir = dirname(__FILE__).'/../../data/Move/';

        for ($i = 2; $i <= 50; $i++) {
            $fichier = $outputDir .'mov'. $i . '.png';

            if (file_exists($fichier)) {
                unlink($fichier);
            }
        }
        $getFileSize = function ($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        $contentLength = 0;
        $headers = explode("\r\n", $response);
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Length:') === 0) {
                $contentLength = (int) trim(substr($header, 15));
                break;
            }
        }

        curl_close($ch);
        return $contentLength > 0 ? $contentLength : false;
    };
        $startIndex = 2;
        $endIndex = 50;
        $minSize = 196;
        for ($index = $startIndex; $index <= $endIndex; $index++) {

        $downloadFile = function ($url, $savePath) {
        $ch = curl_init($url);
        $fp = fopen($savePath, 'wb');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        curl_exec($ch);
        fclose($fp);
        curl_close($ch);
        };

        $urlLive = 'http://' . trim($eqLogic->getConfiguration('addr')) 
                 . '/bha-api/history.cgi?http-user=' . trim($eqLogic->getConfiguration('user')) 
                 . '&http-password=' . trim($eqLogic->getConfiguration('pass')) 
                 . '&event=motionsensor&index=' . $index;

        $fileSize = $getFileSize($urlLive);
        if ($fileSize === false) {
           log::add('doorbirdv2', 'debug', 'Impossible de déterminer la taille du fichier à l URL : '.$urlLive);
            continue;
        }

        if ($fileSize < $minSize) {
            log::add('doorbirdv2', 'debug', 'Fichier ignoré :'. $urlLive .'(taille = '. $fileSize .' bytes, inférieure à '. $minSize .' bytes)');
             break;
        }

        $savePath = $outputDir . 'mov'.$index.'.png';
        log::add('doorbirdv2', 'debug', 'Téléchargement du fichier depuis '. $urlLive .' (taille = '. $fileSize .'bytes)...');
        $downloadFile($urlLive, $savePath);
        log::add('doorbirdv2', 'info', 'Fichier Move sauvegardé sous '. $savePath);
        }
      
        try {

            $fichiers = scandir($outputDir);
            $nombreFichiers = count($fichiers);
                file_put_contents(dirname(__FILE__) . '/../../data/countMove.php', json_encode([
                    'status' => 'success',
                    'count' => ($nombreFichiers-3),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (Exception $e) {
                    $e->getMessage();
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
