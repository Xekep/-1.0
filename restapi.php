<?php
class restapi
{
    private $token;
    private const ARSHIN_BASE_URL = "https://fgis.gost.ru/fundmetrology/cm/";
    
    function __construct($token) {
        $this->token = $token;
    }
    
    function get_report(int $id): ?string
    {
        $url = self::ARSHIN_BASE_URL . "api/applications/$id/protocol";
        $options = array('http' => array(
            'method'  => 'GET',
            'header' => 'Authorization: Bearer '. $this->token
        ));
        $context  = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        return !$response ? null : str_replace('gost:', '', $response);
    }

    function get_report_data(int $id): ?array
    {
        $verificationData = [];

        // Получение данных для формирования XML
        $report = $this->get_report($id);
        if(is_null($report)) return null;
        $xml_protocol = new SimpleXMLElement($report);
        $records = $xml_protocol->children()->appProcessed->record;

        foreach ($records as $record) {
            $verification_id = (string)$record->success->globalID;
            $verification = (json_decode($this->verification($verification_id)))->result;
            $modification = $verification->miInfo->singleMI->modification;
            $vrfDate = date('Y-m-d', strtotime($verification->vriInfo->vrfDate));
            $validDate = null;
            if(isset($verification->vriInfo->validDate))
                $validDate = date('Y-m-d', strtotime($verification->vriInfo->validDate));
            $conclusion = isset($verification->vriInfo->applicable) ? 1 : 2; // 1 - пригоден, 2 - непригоден
            $verificationData[] = [
                'TypeMeasuringInstrument' => $modification,
                'DateVerification' => $vrfDate,
                'DateEndVerification' => $validDate,
                'ResultVerification' => $conclusion,
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
        $response = @file_get_contents($url, false, $context);
        return !$response ? null : str_replace('gost:', '', $response);
    }
    function verification(int $id): ?string
    {
        $url = self::ARSHIN_BASE_URL . "iaux/vri/$id";
        $options = array('http' => array(
            'method'  => 'GET'
        ));
        $context  = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        return !$response ? null : $response;
    }
}
?>
