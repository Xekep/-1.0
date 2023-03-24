<?php
//error_reporting(0);
require_once('config.php');
require_once('restapi.php');

function sendResponce($filename, $data_text)
{
    header('Content-type: application/txt');
	header('Content-Length: ' . strlen($data_text));
	header('Content-Description: File Transfer');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: no-cache, must-revalidate');
	header('Content-Disposition: attachment; filename="'.$filename.'.js"');
	echo $data_text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //$params = json_decode(base64_decode(['data']));
    if(!isset($_POST['protocol'])) exit();
    if(!isset($_POST['metrologist'])) exit();
    $protocol_id = $_POST['protocol'];
    $metrologist = $_POST['metrologist'];
    $api = new restapi(AUTH_TOKEN);
    try
    {
        $data = Array();
        $xml_protocol = new SimpleXMLElement($api->get_report($protocol_id));
        $records = $xml_protocol->children()->appProcessed->record;
        foreach($records as $record)
        {
            $verification_id = (string)$record->success->globalID;
            $verification = json_decode($api->verification($verification_id));
            array_push($data, [
                'typeSI' => $verification->result->miInfo->singleMI->modification,
                'verifDate' => $verification->result->vriInfo->vrfDate,
                'validDate' => $verification->result->vriInfo->validDate,
                'conclusion' => isset($verification->result->vriInfo->applicable) ? 'Пригодно' : 'Непригодно',
                'verifSurname' => $metrologist,
                'arshNum' => $verification_id
            ]);
        }
        $template_text = file_get_contents('template.txt');
        $data_text = str_replace('{data}', json_encode($data, JSON_UNESCAPED_UNICODE), $template_text);
        sendResponce("JS_$protocol_id", $data_text);
        exit();
        
    }
    catch (Exception $e) 
    {
        
    }
}
else
{
    
}
http_response_code(406);
?>