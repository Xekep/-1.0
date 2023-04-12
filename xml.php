<?php
//error_reporting(0);
require_once('config.php');
require_once('restapi.php');
require_once('functions.php');

// Функция для возврата ошибки 406 и завершения скрипта
function err()
{
    http_response_code(406);
    exit();
}

// Функция для отправки ответа с XML-данными в виде вложения
function sendResponse($filename, $data_text)
{
    header('Content-type: application/txt');
    header('Content-Length: ' . strlen($data_text));
	header('Content-Description: File Transfer');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: no-cache, must-revalidate');
	header('Content-Disposition: attachment; filename="'.$filename.'.xml"');
	echo $data_text;
}

// Функция для создания XML-документа на основе переданных параметров
function createXML($firstName, $lastName, $snils, $records): ?string
{
    if (empty($records)) {
        return null;
    }
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Message></Message>');

    // Создание элемента VerificationMeasuringInstrumentData
    $verificationMeasuringInstrumentData = $xml->addChild('VerificationMeasuringInstrumentData');
    foreach($records as $record)
    {
        $verificationMeasuringInstrument = $verificationMeasuringInstrumentData->addChild('VerificationMeasuringInstrument');
        $verificationMeasuringInstrument->addChild('NumberVerification', $record['NumberVerification']);
        $verificationMeasuringInstrument->addChild('DateVerification', $record['DateVerification']);
        $verificationMeasuringInstrument->addChild('DateEndVerification', $record['DateEndVerification']);
        $verificationMeasuringInstrument->addChild('TypeMeasuringInstrument', $record['TypeMeasuringInstrument']);
        $approvedEmployee = $verificationMeasuringInstrument->addChild('ApprovedEmployee');
        $name = $approvedEmployee->addChild('Name');
        $name->addChild('Last', $lastName);
        $name->addChild('First', $firstName);
        $approvedEmployee->addChild('SNILS', $snils);
        $verificationMeasuringInstrument->addChild('ResultVerification', $record['ResultVerification']);
    }

    // Создание элемента SaveMethod
    $xml->addChild('SaveMethod', '1'); // 1 - черновик, 2 - отправлено

    // Сохранение XML файла
    return $xml->asXML();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['protocol'], $_POST['metrologist_id'])) {
    err();
}

$metrologists = getMetrologistsList();
if (!$metrologists || !is_numeric($_POST['metrologist_id']) || $_POST['metrologist_id'] >= count($metrologists)) {
    err();
}

$protocol_id = $_POST['protocol'];
$metrologist = $metrologists[$_POST['metrologist_id']];
$firstName = $metrologist['FirstName'];
$lastName = $metrologist['LastName'];
$snils = $metrologist['SNILS'];

$api = new restapi(AUTH_TOKEN);

try {
    $verificationData = [];

    // Получение данных для формирования XML
    $xml_protocol = new SimpleXMLElement($api->get_report($protocol_id));
    $records = $xml_protocol->children()->appProcessed->record;

    foreach ($records as $record) {
        $verification_id = (string)$record->success->globalID;
        $verification = json_decode($api->verification($verification_id));

        $verificationData[] = [
            'TypeMeasuringInstrument' => $verification->result->miInfo->singleMI->modification,
            'DateVerification' => $verification->result->vriInfo->vrfDate,
            'DateEndVerification' => $verification->result->vriInfo->validDate,
            'ResultVerification' => isset($verification->result->vriInfo->applicable) ? 1 : 2, // 1 - пригоден, 2 - непригоден
            'NumberVerification' => $verification_id,
        ];
    }

    // Формирование XML и отправка данных
    $xml = createXML($firstName, $lastName, $snils, $verificationData);
    sendResponse("$protocol_id", $xml);
} catch (Exception $e) {
    err();
}
?>