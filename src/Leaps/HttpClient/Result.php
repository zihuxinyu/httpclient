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

class Result {
	protected $body;
	protected $httpCode = 0;
	protected $headers = [ ];
	protected $cookies = [ ];
	protected $time = 0;

	/**
	 * 构造方法
	 *
	 * @param array $data
	 */
	public function __construct(array $data) {
		if (isset ( $data ['code'] ))
			$this->httpCode = $data ['code'];
		if (isset ( $data ['time'] ))
			$this->time = $data ['time'];
		if (isset ( $data ['data'] ))
			$this->body = $data ['data'];

		if (isset ( $data ['header'] ) && is_array ( $data ['header'] ))
			foreach ( $data ['header'] as $item ) {
				if (preg_match ( '#^([a-zA-Z0-9\-]+): (.*)$#', $item, $m )) {
					if ($m [1] == 'Set-Cookie') {
						if (preg_match ( '#^([a-zA-Z0-9\-_.]+)=(.*)$#', $m [2], $m2 )) {
							if (false !== ($pos = strpos ( $m2 [2], ';' ))) {
								$m2 [2] = substr ( $m2 [2], 0, $pos );
							}
							$this->cookies [$m2 [1]] = $m2 [2];
						}
					} else {
						$this->headers [$m [1]] = $m [2];
					}
				}
			}
	}

	/**
	 * 获取响应内容
	 *
	 * @return array
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * 获取响应代码
	 *
	 * @return number
	 */
	public function getHttpCode() {
		return $this->httpCode;
	}

	/**
	 * 获取Header内容
	 *
	 * @param string $key
	 * @return multitype:
	 */
	public function getHeader($key = null) {
		if (null === $key) {
			return $this->headers;
		} else {
			return $this->headers [$key];
		}
	}

	/**
	 * 获取Cookie内容
	 *
	 * @param string $key
	 * @return multitype:
	 */
	public function getCookie($key = null) {
		if (null === $key) {
			return $this->cookies;
		} else {
			return $this->cookies [$key];
		}
	}

	/**
	 * 获取请求时间
	 *
	 * @return number
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * 魔术方法，输出内容
	 *
	 * @return string
	 */
	public function __toString() {
		return ( string ) $this->getBody ();
	}

	/**
	 * 输出数组
	 */
	public function toArray(){
		return get_object_vars($this);
	}
}