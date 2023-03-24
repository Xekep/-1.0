<?php
class restapi
{
    private $token;
    private const ARSHIN_BASE_URL = "https://fgis.gost.ru/fundmetrology/cm/";
    
    function __construct($token) {
        $this->token = $token;
    }
    
    function get_report($id)
    {
        $url = self::ARSHIN_BASE_URL . "api/applications/$id/protocol";
        $options = array('http' => array(
            'method'  => 'GET',
            'header' => 'Authorization: Bearer '. $this->token
        ));
        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return str_replace('gost:', '', $response);
    }
    function status($id)
    {
        $url = self::ARSHIN_BASE_URL . "api/applications/$id/status";
        $options = array('http' => array(
            'method'  => 'GET',
            'header' => 'Authorization: Bearer '. $this->token
        ));
        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return str_replace('gost:', '', $response);
    }
    function verification($id)
    {
        $url = self::ARSHIN_BASE_URL . "iaux/vri/$id";
        $options = array('http' => array(
            'method'  => 'GET'
        ));
        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return $response;
    }
}
?>