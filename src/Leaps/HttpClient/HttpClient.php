<?php
/*
 +------------------------------------------------------------------------+
 | Leaps Framework                                                        |
 +------------------------------------------------------------------------+
 | Copyright (c) 2011-2014 Leaps Team (http://www.tintsoft.com)           |
 +------------------------------------------------------------------------+
 | This source file is subject to the Apache License that is bundled      |
 | with this package in the file docs/LICENSE.txt.                        |
 |                                                                        |
 | If you did not receive a copy of the license and are unable to         |
 | obtain it through the world-wide-web, please send an email             |
 | to license@tintsoft.com so we can send you a copy immediately.         |
 +------------------------------------------------------------------------+
 | Authors: XuTongle <xutongle@gmail.com>                                 |
 +------------------------------------------------------------------------+
 */

namespace Leaps\HttpClient;

class HttpClient
{

	/**
	 * Http驱动实例
	 *
	 * @var \Leaps\HttpClient\AdapterInterface
	 */
	protected $driver;

	/**
	 * 创建一个新的Http驱动实例。
	 *
	 * @param string $adapter
	 */
	public function __construct()
	{
		$reflection = new \ReflectionClass ( "\\Leaps\\HttpClient\\Adapter\\" . $this->getDefaultDriver () );
		$this->driver = $reflection->newInstance ();
	}

	/**
	 * 设置User-Agent
	 *
	 * @param string $agent
	 * @return \Leaps\HttpClient\Repository
	 */
	public function setUserAgent($userAgent)
	{
		$this->driver->setUserAgent ( $userAgent );
		return $this;
	}

	/**
	 * 设置页面来源
	 *
	 * @param string $urlReferer
	 * @return \Leaps\HttpClient\Repository
	 */
	public function setReferer($urlReferer)
	{
		$this->driver->setReferer ( $urlReferer );
		return $this;
	}

	/**
	 * 设置超时时间
	 *
	 * @param number $timeout
	 * @return \Leaps\HttpClient\Repository
	 */
	public function setTimeout($timeout)
	{
		$this->driver->setTimeout ( $timeout );
		return $this;
	}

	public function setCookie($cookie){
		$this->driver->setCookie( $cookie );
		return $this;
	}



	/**
	 * HTTP GET方式请求
	 *
	 * @param string $url
	 * @return \Leaps\HttpClient\Result
	 */
	public function get($url)
	{
		$this->driver->get ( $url );
		$data = $this->driver->getResutData ();
		if (is_array ( $url )) {
			// 如果是多个URL
			$result = [ ];
			foreach ( $data as $key => $item ) {
				$reflection = new \ReflectionClass ( "\\Leaps\\HttpClient\\Result" );
				$result [$key] = $reflection->newInstanceArgs ( [ $item ] );
			}
		} else {
			$reflection = new \ReflectionClass ( "\\Leaps\\HttpClient\\Result" );
			$result = $reflection->newInstanceArgs ( [ $data ] );
		}
		return $result;
	}

	/**
	 * Http POST方式请求
	 *
	 * @param string $url
	 * @param string $data
	 * @return \Leaps\HttpClient\Result
	 */
	public function post($url, $datas)
	{
		$this->driver->post ( $url, $datas );
		$data = $this->driver->getResutData ();
		if (is_array ( $url )) {
			// 如果是多个URL
			$result = [ ];
			foreach ( $data as $key => $item ) {
				$reflection = new \ReflectionClass ( "\\Leaps\\HttpClient\\Result" );
				$result [$key] = $reflection->newInstanceArgs ( [ $item ] );
			}
		} else {
			$reflection = new \ReflectionClass ( "\\Leaps\\HttpClient\\Result" );
			$result = $reflection->newInstanceArgs ( [ $data ] );
		}
		return $result;
	}

	/**
	 * PUT方式请求
	 *
	 * @param string $url
	 * @param string、array $data
	 * @return \Leaps\HttpClient\Result
	 */
	public function put($url, $datas)
	{
		$this->driver->put ( $url, $datas );
		$data = $this->driver->getResutData ();
		if (is_array ( $url )) {
			// 如果是多个URL
			$result = [ ];
			foreach ( $data as $key => $item ) {
				$reflection = new \ReflectionClass ( "\\Leaps\\HttpClient\\Result" );
				$result [key] = $reflection->newInstanceArgs ( [ $item ] );
			}
		} else {
			$reflection = new \ReflectionClass ( "\\Leaps\\HttpClient\\Result" );
			$result = $reflection->newInstanceArgs ( [ $data ] );
		}
		return result;
	}

	/**
	 * DELETE方式请求
	 *
	 * @param $url
	 * @param $data
	 * @param $timeout
	 * @return \Leaps\HttpClient\Result
	 */
	public function delete($url)
	{
		$this->driver->delete ( $url );
		$data = $this->driver->getResutData ();
		if (is_array ( $url )) {
			// 如果是多个URL
			$result = [ ];
			foreach ( $data as $key => $item ) {
				$reflection = new \ReflectionClass ( "\\Leaps\\HttpClient\\Result" );
				$result [$key] = $reflection->newInstanceArgs ( [ $item ] );
			}
		} else {

			$reflection = new \ReflectionClass ( "\\Leaps\\HttpClient\\Result" );
			$result = $reflection->newInstanceArgs ( [ $data ] );
		}
		return $result;
	}

	/**
	 * 上传文件
	 *
	 * 注意，使用 `addFile()` 上传文件时，必须使用post方式提交
	 *
	 * upload('http://localhost/upload', '/tmp/test.jpg');
	 *
	 * @param $url
	 * @param $name string 上传的文件的key，默认为 `file`
	 * @param $file_name string
	 * @param null $post
	 * @param int $timeout
	 * @return Result
	 */
	public function upload($url, $fileName, $name = "upload", $post = null, $timeout = 30)
	{
		return $this->addFile ( $fileName, $name )->post ( $url, $post, $timeout );
	}

	/**
	 * 添加上传文件
	 *
	 * HttpClient::factory()->add_file('/tmp/test.jpg', 'img');
	 *
	 * @param $file_name string 文件路径
	 * @param $name string 名称
	 * @return $this
	 */
	public function addFile($fileName, $name = "upload")
	{
		$this->driver->addFile ( $fileName, $name ? $name : "upload" );
		return this;
	}

	/**
	 * 获取默认驱动程序名称
	 *
	 * @return Ambigous <\Leaps\mixed, mixed, array, unknown, Closure>
	 */
	public function getDefaultDriver()
	{
		if (function_exists ( "curl_init" )) {
			return "Curl";
		} else {
			return "Fsock";
		}
	}

	/**
	 * 获取适配器实例。
	 *
	 * @return \Leaps\HttpClient\AdapterInterface
	 */
	public function getDriver()
	{
		return $this->driver;
	}

	/**
	 * 设置，获取REST的类型
	 *
	 * @param string $method GET|POST|DELETE|PUT 等，不传则返回当前method
	 *
	 * @return string
	 * @return HttpClient_Result
	 */
	public function method($method = null)
	{
		if (null===$method){
			return $this->driver->method();
		}
		$this->driver->method(strtoupper($method));
		return $this;
	}

	/**
	 * 魔术方法，直接访问驱动的方法
	 *
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	public function __call($method, $params)
	{
		if (method_exists ( $this->driver, $method )) {
			return call_user_func_array ( [ $this->driver,$method ], $params );
		}
	}
}