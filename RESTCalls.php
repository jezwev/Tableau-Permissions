<?php

// Sign in to server and get Site ID and Authentication Token
function curlTableauToken($username, $password, $URL, $site,$APIVersion,&$authToken,&$siteToken)
{
    $payload = '
              <tsRequest>
              <credentials name="' . $username . '" password="' . $password . '" >
              <site contentUrl="' . $site . '" />
              </credentials>
              </tsRequest>
              ';
    
    $ch = curl_init($URL . $APIVersion . "auth/signin");
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: text/xml'
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    try {
        
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        /*
         * Format of returned XML
         * <?xml version="1.0" encoding="UTF-8"?>
         * <tsResponse version-and-namespace-settings>
         * <credentials token="12ab34cd56ef78ab90cd12ef34ab56cd">
         * <site id="9a8b7c6d5-e4f3-a2b1-c0d9-e8f7a6b5c4d" contentUrl=""/>
         * <user id="9f9e9d9c-8b8a-8f8e-7d7c-7b7a6f6d6e6d" />
         * </credentials>
         * </tsResponse>
         *
         */
        
        $xml = simplexml_load_string($output);
        if ($info != FALSE && $info['http_code'] == 200 && $xml != FALSE) {
            //  $GLOBALS['authToken'] = $xml->credentials->attributes()->token;
            //  $GLOBALS['siteToken'] = $xml->credentials->site->attributes()->id;
            $authToken=(string)$xml->credentials->attributes()->token;
            $siteToken=(string)$xml->credentials->site->attributes()->id;
            
            return;
        }
        if ($info != FALSE && $info['http_code'] == 0) {
            debug_to_console('Error connecting to server at: ' . $URL);
            die();
        }
        // see what teh error in the reponse was
        if ($xml != FALSE && $xml->error->detail != null) {
            debug_to_console("Error: " . $xml->error->detail .'. Code: '.$xml->error->attributes()->code);
            die();
        }
    } catch (Exception $e) {
        debug_to_console("Error (File: " . $e->getFile() . ", line " . $e->getLine() . "): " . $e->getMessage());
        die();
    }
}

function curlTableauXML($url, $token = "")
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-tableau-auth: $token"
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($output);
    
    if ($xml->error->detail != null) {
        debug_to_console("Error: " . $xml->error->detail);
        die();
    }
    
    return $xml;
}

// POST to Server
function curlTableauPost($URL, $token, $payLoad)
{
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-tableau-auth: $token"
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payLoad);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($output);
    
    if ($xml->error->detail != null) {
        return "Error: " . $xml->error->detail . ' Code:' . $xml->error->attributes()->code;
    }
    
    return $xml;
}

// POST to Server
function curlTableauPut($URL, $token, $payLoad)
{
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ($payLoad));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-tableau-auth: $token"
    ));
    
    $output = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($output);
    
    if ($xml->error->detail != null) {
        return "Error: " . $xml->error->detail . ' Code:' . $xml->error->attributes()->code;
    }
    
    return $xml;
}

// DELETE to Server
function curlTableauDelete($URL, $token)
{
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-tableau-auth: $token"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $xml = simplexml_load_string($output);
    
    if ($info['http_code'] == 204)
        return 'OK';
        if ($xml != false && $xml->error->detail != null) {
            return ("Error: " . $xml->error->detail);
        }
        
        return 'Error ' . $info['http_code'];
}