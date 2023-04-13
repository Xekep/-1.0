<?php
//error_reporting(0);
require_once('config.php');
require_once('restapi.php');
require_once('functions.php');

// Функция для отдачи файла
function fileForceDownload($fileName, $file) {
  if (file_exists($file)) {
    // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
    // если этого не сделать файл будет читаться в память полностью!
    if (ob_get_level()) {
      ob_end_clean();
    }
    // заставляем браузер показать окно сохранения файла
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $fileName);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    // читаем файл и отправляем его пользователю
    readfile($file);
    exit;
  }
}

// Функция для возврата ошибки 406 и завершения скрипта
function err() {
  http_response_code(406);
  exit();
}

// Функция для создания архива XML-документов
function createArchive($fileNamePrefix, array $files): ?string {
  if (empty($files)) {
    return null;
  }

  $archiveName = tempnam(sys_get_temp_dir(), 'archive');
  $zip = new ZipArchive();
  if ($zip->open($archiveName, ZipArchive::CREATE) === true) {
    $counter = 0;
    foreach ($files as $file) {
      $zip->addFile($file, $fileNamePrefix.'_part'.(++$counter).'.xml');
    }
    $zip->close();
    return $archiveName;
  } else {
    return null;
  }
}

function createXML($firstName, $lastName, $snils, $records): array {
  if (empty($records)) {
    return [];
  }

  $fileCounter = 0;
  $xmlArray = [];
  $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Message></Message>');

  // Создание элемента VerificationMeasuringInstrumentData
  $verificationMeasuringInstrumentData = $xml->addChild('VerificationMeasuringInstrumentData');

  foreach($records as $index => $record) {
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

    // Создание нового файла XML при достижении максимального количества записей
    if (($index + 1) % 999 === 0) {
      $fileCounter++;
      $fileName = tempnam(sys_get_temp_dir(), 'xml');
      $xml->addChild('SaveMethod', '1'); // 1 - черновик, 2 - отправлено
      $xmlString = $xml->asXML();
      file_put_contents($fileName, $xmlString);
      $xmlArray[] = $fileName;
      unset($xml);
      $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Message></Message>');
      $verificationMeasuringInstrumentData = $xml->addChild('VerificationMeasuringInstrumentData');
    }
  }

  // Добавление оставшихся записей в последний файл
  if (count($verificationMeasuringInstrumentData->children()) > 0) {
    $fileCounter++;
    $fileName = tempnam(sys_get_temp_dir(), 'xml');
    $xml->addChild('SaveMethod', '1'); // 1 - черновик, 2 - отправлено
    $xmlString = $xml->asXML();
    file_put_contents($fileName, $xmlString);
    $xmlArray[] = $fileName;
  }

    return $xmlArray;
}
    
if (!isset($_POST['protocol'], $_POST['metrologist_id'])) {
  err();
}

$metrologists = getMetrologistsList();

if (!$metrologists || !is_numeric($_POST['metrologist_id']) || !is_numeric($_POST['protocol']) || $_POST['metrologist_id'] >= count($metrologists)) {
  err();
}

$protocol_id = (int)$_POST['protocol'];
$metrologist =  (int)$metrologists[$_POST['metrologist_id']];
$firstName = $metrologist['FirstName'];
$lastName = $metrologist['LastName'];
$snils = $metrologist['SNILS'];

$api = new restapi(AUTH_TOKEN);
    
// Получаем записи по выбранному протоколу
$records = $api->get_report_data($protocol_id);
if(is_null($records)) err();
// Создание XML-файлов
$xmlFiles = createXML($firstName, $lastName, $snils, $records);
if(count($xmlFiles) == 1)
{
  fileForceDownload($protocol_id . '.xml', $xmlFiles[0]);
} else {
  // Создание архива из XML-файлов
  $archiveName = createArchive($protocol_id, $xmlFiles);

  if ($archiveName) {
    // Отдаем архив пользователю
    fileForceDownload($protocol_id . '.zip', $archiveName);
    // Удаление временных файлов
    foreach ($xmlFiles as $xmlFile) {
      unlink($xmlFile);
    }
    unlink($archiveName);
  } else {
    // Возвращаем ошибку 406
    err();
  }
}
?>
