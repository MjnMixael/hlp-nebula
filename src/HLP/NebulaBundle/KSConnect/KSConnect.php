<?php

/*
* Copyright 2014 HLP-Nebula authors, see NOTICE file

*
* Licensed under the EUPL, Version 1.1 or – as soon they
will be approved by the European Commission - subsequent
versions of the EUPL (the "Licence");
* You may not use this work except in compliance with the
Licence.
* You may obtain a copy of the Licence at:
*
*
http://ec.europa.eu/idabc/eupl

*
* Unless required by applicable law or agreed to in
writing, software distributed under the Licence is
distributed on an "AS IS" basis,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
express or implied.
* See the Licence for the specific language governing
permissions and limitations under the Licence.
*/

namespace HLP\NebulaBundle\KSConnect;

class KSConnect
{
  protected $sendURL;
  
  protected $retrieveURL;
  
  protected $scriptURL;
  
  protected $wsURL;
  
  protected $APIkey;
  
  protected $ksconn;
  
  public function __construct($server, $APIkey, $secure)
  {
    if($secure)
    {
      $urlBase = 'https://' . $server;
      $wsBase = 'wss://' . $server;
    } else {
      $urlBase = 'http://' . $server;
      $wsBase = 'ws://' . $server;
    }
    
    $this->apiURL = $urlBase . '/api';
    $this->scriptURL = $urlBase . '/static/converter.js';
    $this->wsURL = $wsBase . '/ws/converter';
    
    $this->APIkey = $APIkey;
    
    $this->ksconn = curl_init();
    curl_setopt($this->ksconn, CURLOPT_FAILONERROR, true);
    curl_setopt($this->ksconn, CURLOPT_RETURNTRANSFER, true);
  }
  
  public function getScriptURL()
  {
    return $this->scriptURL;
  }
  
  public function getWsURL()
  {
    return $this->wsURL;
  }
  
  public function requestConversion($data, $webhook)
  {
    return $this->_callApi('request', array(
        'passwd'  => $this->APIkey, 
        'data'    => $data,
        'webhook' => $webhook
    ));
  }
  
  public function retrieveConverted($ticket, $token)
  {
    return $this->_callApi('retrieve', array(
			  'ticket' => $ticket, 
			  'token'  => $token,
	  ));
  }

  protected function _callApi($method, $fields)
  {
    curl_setopt($this->ksconn, CURLOPT_URL, $this->apiURL . '/' . $method);
    curl_setopt($this->ksconn, CURLOPT_POST, count($fields));
    curl_setopt($this->ksconn, CURLOPT_POSTFIELDS, http_build_query($fields));
    
    return json_decode(curl_exec($this->ksconn));
  }
  
  public function __destruct()
  {
    curl_close($this->ksconn);
  }
}
