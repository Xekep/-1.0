<?php
function getMetrologistsList(): ?array {
    // Чтение файла с данными metrologists
    $json_data = file_get_contents('metrologists.json');
    // Декодирование JSON-строки в массив PHP
    $metrologists = json_decode($json_data, true);
    if(!isset($metrologists['metrologists'])) return null;
    $metrologists = $metrologists['metrologists'];
    return $metrologists;
}
?>