<?php

ini_set('ignore_repeated_errors', true);
ini_set('display_errors', false);
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__). '/pay_errors.log');


require_once('api/Simpla.php');

class Lava extends Simpla
{
	
	public function checkout_form($order_id, $button_text = null)
	{
		if($this->request->method('post') && $this->request->post('go')) {
			$this->redirect($order_id);
		} else {
			if(empty($button_text)) {
				$button_text = 'Перейти к оплате';
			}
			$button = '<form method="post"><input name="go" class="button" type="submit" value="'.$button_text.'"></form>';
			return $button;
		}
	}
	
	
	public function redirect($order_id)
	{
		/* Получение секретного ключа (выполняется один раз) */
		/*
		https://Вашсайт/payment/Lava/generate-secret-key.php
		*/
		/* Сохраняете Ваш секретный ключ в конфиг и используете его для генерации сигнатуры в устаревшем методе */
		
		
		
		$order = $this->orders->get_order((int)$order_id);

		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$price = $this->money->convert($order->total_price, $payment_method->currency_id, false);
		$success_url = $this->config->root_url.'/order/'.$order->url;
		$fail_url = $this->config->root_url.'/order/'.$order->url;
		$settings = $this->payment->get_payment_settings($payment_method->id);
		$amount = $this->money->convert($order->total_price, $payment_method->currency_id, false);
		$amount = number_format($amount, 2, '.', '');
		$shopId = $settings['shopId'];
		$secret_key = $settings['secret_key'];
		$secret_key2 = $settings['secret_key2'];
		$cr = $payment_currency->code == 'RUR' ? 'RUB' : $payment_currency->code;
		$comment = 'Оплата заказа №'.$order->id;
		
        $num = time();

		$data = array(
			'sum' => $amount,
			'orderId' => $order->id.'_'.$num,
			'shopId' => $shopId,
			'hook_url' => $settings['hook_url'],
			'success_url' => $success_url,
			'fail_url' => $fail_url,
			'expire' => 1440,
			'comment' => $comment,
			'merchant_name' => $settings['merchant_name']
		);

		ksort($data);

		// Сама сигнатура
        $signature= hash_hmac("sha256", json_encode($data), $secret_key);
        $signature_arr = array('signature' => $signature);
		$data = $data + $signature_arr;
		//$data = json_encode($data + ['signature' => $signature]);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.lava.ru/business/invoice/create");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json","Content-Type: application/json"));
		$response = json_decode(curl_exec($ch),true);
		curl_close($ch);
		
		var_dump($response);
		
		if ($response['status'] == '200') {
			header('Location: '.$response['data']['url']);
			exit;
		} elseif ($response['status'] == '422') {
			echo '<div class="error">
				<h3 class="title_error">Ошибка платежной системы</h3>
			</div>';
		} else {
			echo '<div class="error">
				<h3 class="title_error">Ошибка платежной системы</h3>
			</div>';
		}
	}
}
