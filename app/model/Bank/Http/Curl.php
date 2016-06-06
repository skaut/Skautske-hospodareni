<?php

namespace Model\Bank\Http;

class Curl implements IClient
{

	/**
	 * @param string $url
	 * @param int $timeout
	 * @return Response
	 */
	public function get($url, $timeout)
	{
		$ch = curl_init($url);
		// Include header in result? (0 = yes, 1 = no)
		curl_setopt($ch, CURLOPT_HEADER, 1);
		// Should cURL return or print out the data? (true = return, false = print)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$response = curl_exec($ch);
		$header = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
		$body = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));

		if ($header == NULL && curl_errno($ch) == 28) {
			return new Response(NULL, NULL, TRUE);
		}

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		return new Response($code, $body, FALSE);
	}

}