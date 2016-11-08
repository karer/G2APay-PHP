<?php

/**
 * G2APay PHP Library
 * @author  	Kacper "karer" Geisheimer
 * @copyright 	Copyright (c) 2016 Kacper Geisheimer
 * @license 	https://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Karer;

class G2APayment
{
	const API_URL = 'https://checkout.pay.g2a.com';

	private $apiHash;
	private $apiSecret;
	private $urlSuccess;
	private $urlFail;
	private $orderId;
	private $currency;
	private $items = [];

	public function __construct($apiHash, $apiSecret, $urlSuccess, $urlFail, $orderId, $currency = 'USD')
	{
		$this->apiHash 		= $apiHash;
		$this->apiSecret 	= $apiSecret;
		$this->urlSuccess 	= $urlSuccess;
		$this->urlFail		= $urlFail;
		$this->orderId 		= $orderId;
		$this->currency 	= $currency;
	}

	public function addItem($sku, $name, $quanity, $id, $price, $url, $extra = '', $type = '')
	{
		$this->items[] = [
			'sku'		=> $sku,
			'name'		=> $name,
			'amount'	=> $quanity * $price,
			'qty'	=> $quanity,
			'id'		=> $id,
			'price'		=> $price,
			'url'		=> $url,
			'extra'		=> $extra,
			'type'		=> $type,
		];
	}

	public function create()
	{
		// Calculate total price of items.
		$amount = array_sum(array_column($this->items, 'amount'));

		// Prepare array with data to query G2A.
		$fields = [
			'api_hash'		=> $this->apiHash,
			'hash'			=> $this->calculateHash($amount),
			'order_id'		=> $this->orderId,
			'amount'		=> $amount,
			'currency'		=> $this->currency,
			'url_failure'	=> $this->urlFail,
			'url_ok'		=> $this->urlSuccess,
			'items'			=> $this->items,
		];

		// Request API server.
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, self::API_URL . '/index/createQuote');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

		$response = curl_exec($ch);
		curl_close($ch);
		
		// Convert response from JSON format to native PHP array.
		$result = json_decode($response);

		if (isset($result->token)) {
			return [
				'success' => true,
				'url' => (self::API_URL . '/index/gateway?token=' . $result->token)
			];
		} else {
			return [
				'success' => false,
				'message' => $result->msg
			];
		}
	}

	private function calculateHash($amount)
	{
		return hash('sha256', $this->orderId . number_format($amount, 2) . $this->currency . $this->apiSecret);
	}
}