<?php
namespace App\Helpers;

use App\HelpersGenericCurlClient;

class GenericRequest {

	private $session;
	private $method;
	private $path;
	public $params;
	private $version;

	public static function getClientHandler()
	{
		return (new GenericCurlClient());
	}


	protected function getRequestUrl()
	{
		return $this->session->getEnvHostUrl() . DIRECTORY_SEPARATOR . $this->version . DIRECTORY_SEPARATOR . trim($this->path, '/') . DIRECTORY_SEPARATOR;
	}

	/**
	 * appendParamsToUrl - Gracefully appends params to the URL.
	 *
	 * @param string $url
	 * @param array $params
	 *
	 * @return string
	 */
	public static function paramsToUrl($url, $params = array())
	{
		if (!$params) {
			return $url;
		}

		if (strpos($url, '?') === false) {
			return trim($url, '/') . '?' . http_build_query($params);
		}

		list($path, $query_string) = explode('?', $url, 2);
		parse_str($query_string, $query_array);

		$params = array_merge($params, $query_array);

		return $path . '?' . http_build_query($params);
	}

	public static function get($url, $params = false, $logger = false)
	{
		if($params) {
			$url = self::paramsToUrl($url, $params);
		}

		$conn = self::getClientHandler();
		$conn->addHeader('Accept','text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
		$conn->addHeader('Accept-Language', 'en-US,en;q=0.5');
		$conn->addHeader('User-Agent','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:32.0) Gecko/20100101 Firefox/32.0');

		$conn->connect($url, 'GET');

		if($logger) {
			return $logger->execute($conn, $url, $params, 'GET');
		}
		
		return $conn->execute();
	}

	public static function post($url, $params = [], $cookie = false, $logger = false)
	{
		$conn = self::getClientHandler();
		$conn->connect($url, 'POST', $params, $cookie);

		if($logger) {
			return $logger->execute($conn, $url, $params, 'POST');
		}

		$e = $conn->execute();
		return $e;
	}

}
