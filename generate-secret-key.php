<?php

header('Content-type: text/plain');

/* Получение секретного ключа (выполняется один раз)!
Секретные ключи вставлять в файл конфигурации
 */

$curl = curl_init();

$jwt = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOiI4OTU0OTczZC05MGE4LWYyNzYtNmFmZS05NzkyN2Y1MGYyNjciLCJ0aWQiOiJlN2MyZDRkMi00OTgzLTcwMDItOTJhYS04ZjhhNzZlYTI5NDMifQ.DF3syR1EQ77yh0GmnoeR5YyyIjDhyzMzcXvtYfs2E6c"; // Ваш API-ключ

curl_setopt_array($curl, array(
	CURLOPT_URL => 'https://api.lava.ru/invoice/generate-secret-key',
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => '',
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 0,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => 'GET',
	CURLOPT_HTTPHEADER => array(
		'Authorization: ' . $jwt
	),
));

$response = curl_exec($curl);

curl_close($curl);

var_dump($response);
/*
$response = json_decode($response, true);
$secret_key = $response['secret_key']; 
*/

/* Сохраняете Ваш секретный ключ в конфиг и используете его для генерации сигнатуры в устаревшем методе */

