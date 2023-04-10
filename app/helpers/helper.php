<?php

use GuzzleHttp\Client;

function sendOtpSms($code , $mobile){
    $token = json_decode(getSmsToken())->TokenKey;
    $headers = ['Content-Type' => 'application/json' , 'x-sms-ir-secure-token'=>$token];

    $client = new Client([
        // Base URI is used with relative requests
        'base_uri' => 'https://RestfulSms.com/api/',
        // You can set any number of default request options.
        'timeout'  => 2.0,
    ]);
    $body = new \stdClass();
    $ParameterArray = new \stdClass();
    $ParameterArray->Parameter = "VerificationCode";
    $ParameterArray->ParameterValue = $code;
    $body->ParameterArray = [$ParameterArray];
    $body->Mobile = $mobile;
    $body->TemplateId = "66901";
    
    $response = $client->request('POST', 'UltraFastSend',[ 
        'headers'=>$headers, 
        'json' => $body
    ]);

    return $response->getBody();
}

function getSmsToken(){
    $headers = ['Content-Type' => 'application/json'];
    $body = new \stdClass();
    $body->UserApiKey='da1b8c74fd5def31c91b5df9';
    $body->SecretKey='Aria123456@@';
    $client = new Client();
    try{
   $response = $client->request('POST', 'https://RestfulSms.com/api/Token',
   ['headers' => $headers, 
        'json' => $body
    ]);
    $responseBody = $response->getBody();
    // print_r($responseBody);
    return $responseBody;
    }
    catch (GuzzleHttp\Exception\ClientException $e) {
        $response = $e->getResponse();
        $responseBodyAsString = $response->getBody()->getContents();
            return($responseBodyAsString);

    }


}
