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
    protected $url_base;
    protected $ws_url;
    protected $api_num;
    protected $api_key;
    protected $ksconn = null;

    public function __construct($server, $secure, $api_num, $api_key)
    {
        if ($secure) {
            $this->url_base = 'https://' . $server;
            $this->ws_url = 'wss://' . $server;
        } else {
            $this->url_base = 'http://' . $server;
            $this->ws_url = 'ws://' . $server;
        }

        $this->api_num = $api_num;
        $this->api_key = $api_key;
    }

    public function getScriptURL()
    {
        return $this->url_base . '/static/converter.js';
    }

    public function getUploadURL()
    {
        return $this->url_base . '/drop';
    }

    public function getWsURL()
    {
        return $this->ws_url;
    }

    public function requestConversion($data, $webhook)
    {
        return $this->_callApi('request', array(
            'passwd'  => $this->api_key, 
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

    public function generateToken()
    {
        $time = time() + 10;
        $hash = hash('sha256', $this->api_key . $time);

        return $time . '/' . urlencode($hash) . '/' . $this->api_num;
    }

    protected function _callApi($method, $fields)
    {
        if(is_null($this->ksconn)) {
            $this->ksconn = curl_init();
            curl_setopt($this->ksconn, CURLOPT_FAILONERROR, true);
            curl_setopt($this->ksconn, CURLOPT_RETURNTRANSFER, true);
        }

        curl_setopt($this->ksconn, CURLOPT_URL, $this->url_base . '/api/converter/' . $method);
        curl_setopt($this->ksconn, CURLOPT_POST, count($fields));
        curl_setopt($this->ksconn, CURLOPT_POSTFIELDS, http_build_query($fields));

        return json_decode(curl_exec($this->ksconn));
    }

    public function __destruct()
    {
        if(!is_null($this->ksconn)) curl_close($this->ksconn);
    }
}
