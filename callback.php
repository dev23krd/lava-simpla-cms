<?php
chdir ('../../');
require_once('api/Simpla.php');


function send_email_error($message, $order_id = 'null', $to = 'dev403@yandex.ru') {
	if (!empty($to)) {
		$message = "Не удалось провести платёж через систему lava по следующим причинам:\n\n" . $message;
		$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" . 
		"Content-type: text/plain; charset=utf-8 \r\n";
		mail($to, 'Ошибка оплаты', $message, $headers);
	}
	exit($order_id . ' | error | ' . $message);
}

//file_put_contents(__DIR__.'/log_'.date('Y-m-d-H-i-s').'.txt', file_get_contents("php://input"));
//$data = '{"invoice_id":"70cc66d1-caf1-47cd-9271-1ea0e7b387c7","status":"success","pay_time":"2022-11-10 14:12:26","amount":"10.00","order_id":"49391_1668089513","pay_service":"qiwi","payer_details":"79192313986","custom_fields":null,"type":1,"credited":"9.50"}';
$data = file_get_contents("php://input");

if (empty($data)) {
	send_email_error('error empty data');
}

$data = json_decode($data);
if (!is_object($data)) {
	send_email_error('error not object');
}

if (!isset($data->status) || $data->status != 'success') {
	send_email_error('error not success');
}

//header('Content-type: text/plain');var_dump($data);exit;


// загрузка заказа
$simpla = new Simpla();

list($order_id, $num) = explode("_", $data->order_id);

$order = $simpla->orders->get_order(intval($order_id));
if(empty($order)) {
	send_email_error('error empty order');
}

$method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
if(empty($method)) {
	send_email_error('error empty payment method', $order->id);
}

$err = false;
$message = '';
$settings = unserialize($method->settings);


if ($data->amount != $order->total_price) {
	send_email_error('неправильная сумма', $order->id, $settings['lava_email']);
}

//header('Content-type: text/plain');var_dump($data, $settings, $sign_hash);exit;


if ($order->paid) {
	send_email_error('заказ уже оплачен', $order->id, $settings['lava_email']);
}

$simpla->orders->update_order(intval($order->id), array('paid'=>1));
$simpla->orders->close(intval($order->id));
$simpla->notify->email_order_user(intval($order->id));
$simpla->notify->email_order_admin(intval($order->id));

exit('YES');
