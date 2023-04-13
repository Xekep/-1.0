<?php
class restapi
{
    private $token;
    private const ARSHIN_BASE_URL = "https://fgis.gost.ru/fundmetrology/cm/";
    
    function __construct($token) {
        $this->token = $token;
    }
    
    function get_report(int $id): string
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

    function get_report_data(int $id): ?array
    {
        $verificationData = [];

        // Получение данных для формирования XML
        $xml_protocol = new SimpleXMLElement($this->get_report($id));
        $records = $xml_protocol->children()->appProcessed->record;

        foreach ($records as $record) {
            $verification_id = (string)$record->success->globalID;
            $verification = json_decode($this->verification($verification_id));

            $verificationData[] = [
                'TypeMeasuringInstrument' => $verification->result->miInfo->singleMI->modification,
                'DateVerification' => $verification->result->vriInfo->vrfDate,
                'DateEndVerification' => $verification->result->vriInfo->validDate,
                'ResultVerification' => isset($verification->result->vriInfo->applicable) ? 1 : 2, // 1 - пригоден, 2 - непригоден
                'NumberVerification' => $verification_id,
                ];
        }
        return $verificationData;
    }
    function status(int $id): string
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
    function verification(int $id): string
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