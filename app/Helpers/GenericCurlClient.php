<?php
namespace App\Helpers;

class GenericCurlClient {

	const CURL_CONNECTTIMEOUT = 10;
	const CURL_TIMEOUT = 120;

	public $headers;
	public $curler;
	private $curl_connecttimeout;
	private $curl_timeout;


	public function __construct($headers = array(), $curl_connecttimeout = null, $curl_timeout = null)
	{
		$this->headers = $headers;
		$this->curl_connecttimeout = ($curl_connecttimeout ?: static::CURL_CONNECTTIMEOUT);
		$this->curl_timeout = ($curl_timeout ?: static::CURL_TIMEOUT);
	}

	/**
     * Put together options and connect with curl
     *
     * @return void
     */
	public function connect($url, $method = 'GET', $params = [], $cookie = false)
	{
		$curl_opts = [
			CURLOPT_CONNECTTIMEOUT	=> $this->curl_connecttimeout,
     		CURLOPT_TIMEOUT        	=> $this->curl_timeout,
			CURLOPT_SSL_VERIFYHOST	=> false,
     		CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_HEADER			=> false,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_URL				=> $url,
		];

		if($cookie) {
			$curl_opts[CURLOPT_COOKIE] = $cookie;

		}

		if(!is_array($params) && isJson($params)) {
			$curl_opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
		}

		if ($method == 'POST' || $method == 'PUT') {
			$curl_opts[CURLOPT_POSTFIELDS] = $params;
		}

		if ($method == 'PUT') {
			$curl_opts[CURLOPT_CUSTOMREQUEST] = "PUT";
		}

		if ($method == 'DELETE') {
			$curl_opts[CURLOPT_CUSTOMREQUEST] = "DELETE";
		}

		if (!empty($this->headers)) {
			$curl_opts[CURLOPT_HTTPHEADER] = $this->getHeadersOutput();
		}

		$this->curler = curl_init();
		$this->setCurlOptions($curl_opts);
		return $this;
	}
	
	public function execute()
	{
		return curl_exec($this->curler);
	}

	public function info()
	{
		return curl_getinfo($this->curler);
	}

	public function error()
	{
		return curl_error($this->curler);
	}

	public function close()
	{
		curl_close($this->curler);
	}


	public function setCurlOptions(array $options)
	{
		curl_setopt_array($this->curler, $options);
	}


	public function addHeader($key, $value)
	{
		$this->headers[$key] = $value;
	}


	public function getHeaders()
	{
		return $this->headers;
	}

	public function getHeadersOutput()
	{
		$return = [];
		foreach ($this->headers as $key => $value) {
			$return[] = "{$key}: {$value}";
		}

		return $return;
	}

}
