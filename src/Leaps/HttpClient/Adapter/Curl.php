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

namespace Leaps\HttpClient\Adapter;

class Curl
{
	/**
	 * User Agent 浏览器的身份标识
	 *
	 * @var string
	 */
	protected $_userAgent;

	/**
	 * 页面来源
	 *
	 * @var string
	 */
	protected $_referer;

	/**
	 * 携带的Cookie
	 *
	 * @var string
	 */
	protected $_cookie;
	protected $_files = [ ];

	/**
	 * 响应数据寄存器
	 *
	 * @var array
	 */
	protected $_httpData = [ ];
	protected $_ip;
	protected $_header = [ ];
	protected $_option = [ ];
	protected $_timeout = 30;

	protected $proxyHost = null;
	protected $proxyPort = null;

	/**
	 * 待Post提交的数据
	 *
	 * @var array
	 */
	protected $_postData = [ ];

	/**
	 * 多列队任务进程数，0表示不限制
	 *
	 * @var int
	 */
	protected $_multiExecNum = 20;

	/**
	 * 默认请求方法
	 *
	 * @var string
	 */
	protected $_method = 'GET';

	/**
	 * 默认连接超时时间，毫秒
	 *
	 * @var int
	 */
	protected static $_connectTimeout = 3000;

	/**
	 * 设置User Agent
	 *
	 * @param string $userAgent
	 * @return \Leaps\HttpClient\Adapter\Curl
	 */
	public function setUserAgent($userAgent)
	{
		$this->_userAgent = $userAgent;
		return $this;
	}

	/**
	 * 设置Http Referer
	 *
	 * @param string $referer
	 * @return \Leaps\HttpClient\Adapter\Curl
	 */
	public function setReferer($referer)
	{
		$this->_referer = $referer;
		return $this;
	}


	/**
	 * 设置代理服务器访问
	 * @param string $host
	 * @param string $port
	 * @return \Leaps\HttpClient\HttpClient
	 */
	public function setProxy($host,$port){
		$this->proxyHost = $host;
		$this->proxyPort = $port;
		return $this;
	}

	/**
	 * 设置Cookie
	 *
	 * @param string $cookie
	 * @return \Leaps\HttpClient\Adapter\Curl
	 */
	public function setCookie($cookie)
	{
		$this->_cookie = $cookie;
		return $this;
	}


	/**
	 * 设置Header
	 *
	 * @param array $header
	 * @return \Leaps\HttpClient\Adapter\Curl
	 */
	public function setHeader($header)
	{
		$this->_header = array_merge ( $this->_header, ( array ) $header );
		return $this;
	}

	/**
	 * 设置curl参数
	 *
	 * @param string $key
	 * @param value $value
	 * @return \Leaps\HttpClient\Adapter\Curl
	 */
	public function setOption($key, $value)
	{
		if ($key === CURLOPT_HTTPHEADER) {
			$this->_header = array_merge ( $this->_header, $value );
		} else {
			$this->_option [$key] = $value;
		}
		return $this;
	}

	/**
	 * 设置多个列队默认排队数上限
	 *
	 * @param int $num
	 * @return \Leaps\HttpClient\Adapter\Curl
	 */
	public function setMultiMaxNum($num = 0)
	{
		$this->_multiExecNum = ( int ) $num;
		return $this;
	}

	/**
	 * 添加上次文件
	 *
	 * @param $file_name string 文件路径
	 * @param $name string 文件名
	 * @return $this
	 */
	public function addFile($fileName, $name)
	{
		$this->_files [$name] = '@' . $fileName;
		return $this;
	}

	/**
	 * 设置IP
	 *
	 * @param string $ip
	 * @return \Leaps\HttpClient\Adapter\Curl
	 */
	public function setIp($ip)
	{
		$this->_ip = $ip;
		return $this;
	}

	/**
	 * 设置超时时间
	 *
	 * @param int $timeoutp
	 * @return \Leaps\HttpClient\Adapter\Curl
	 */
	public function setTimeout($timeout)
	{
		$this->_timeout = $timeout;
		return $this;
	}

	/**
	 * 设置，获取REST的类型
	 *
	 * @param string $method GET|POST|DELETE|PUT 等，不传则返回当前method
	 * @return string
	 * @return \Leaps\HttpClient\Adapter\Curl
	 */
	public function method($method = null)
	{
		if (null === $method)
			return $this->_method;
		$this->_method = strtoupper ( $method );
		return $this;
	}

	/**
	 * GET方式获取数据，支持多个URL
	 *
	 * @param string/array $url
	 * @return string, false on failure
	 */
	public function get($url)
	{
		if ($this->_method == 'POST') {
			$this->setOption ( CURLOPT_POST, true );
		} else if ($this->_method == 'PUT') {
			$this->setOption ( CURLOPT_PUT, true );
		} else if ($this->_method) {
			$this->setOption ( CURLOPT_CUSTOMREQUEST, $this->_method );
		}
		if (is_array ( $url )) {
			$data = $this->requestUrl ( $url );
		} else {
			$data = $this->requestUrl ( [ $url ] );
		}
		$this->clearSet ();
		if (! is_array ( $url )) {
			$this->_httpData = $this->_httpData [$url];
			return $data [$url];
		} else {
			return $data;
		}
	}

	/**
	 * 用POST方式提交，支持多个URL $urls = array ( 'http://www.baidu.com/',
	 * 'http://mytest.com/url',
	 * 'http://www.abc.com/post', ); $data = array (
	 * array('k1'=>'v1','k2'=>'v2'),
	 * array('a'=>1,'b'=>2), 'aa=1&bb=3&cc=3', );
	 *
	 * @param $url
	 * @param string/array $vars
	 * @return string, false on failure
	 */
	public function post($url, $vars)
	{
		// POST模式
		$this->method ( 'POST' );
		$this->setOption ( CURLOPT_HTTPHEADER, array ('Expect:' ) );
		if (is_array ( $url )) {
			$myvars = [ ];
			foreach ( $url as $k => $u ) {
				if (isset ( $vars [$k] )) {
					if (is_array ( $vars [$k] )) {
						if ($this->_files) {
							$myvars [$u] = $vars [$k] + $this->_files;
						} else {
							$myvars [$u] = http_build_query ( $vars [$k] );
						}
					} else {
						if ($this->_files) {
							// 把字符串解析成数组
							parse_str ( $vars [$k], $tmp );
							$myvars [$u] = $tmp + $this->_files;
						} else {
							$myvars [$u] = $vars [$k];
						}
					}
				}
			}
		} else {
			$myvars = [ $url => $vars ];
		}
		$this->_postData = $myvars;
		return $this->get ( $url );
	}

	/**
	 * PUT方式获取数据，支持多个URL
	 *
	 * @param string/array $url
	 * @param string/array $vars
	 * @return string, false on failure
	 */
	public function put($url, $vars)
	{
		$this->method ( 'PUT' );
		$this->setOption ( CURLOPT_HTTPHEADER, [ 'Expect:' ] );
		if (is_array ( $url )) {
			$myvars = [ ];
			foreach ( $url as $k => $u ) {
				if (isset ( $vars [$k] )) {
					if (is_array ( $vars [$k] )) {
						$myvars [$u] = http_build_query ( $vars [$k] );
					} else {
						$myvars [$u] = $vars [$k];
					}
				}
			}
		} else {
			$myvars = [ $url => $vars ];
		}
		$this->_postData = $myvars;
		return $this->get ( $url );
	}

	/**
	 * DELETE方式获取数据，支持多个URL
	 *
	 * @param string/array $url
	 * @return string, false on failure
	 */
	public function delete($url)
	{
		$this->method ( 'DELETE' );
		$this->get ( $url );
	}

	/**
	 * 创建一个CURL对象
	 *
	 * @param string $url URL地址
	 * @param int $timeout 超时时间
	 * @return curl_init()
	 */
	protected function _create($url)
	{
		$matches = parse_url ( $url );
		$host = $matches ['host'];
		if ($this->_ip) {
			$this->_header [] = 'Host: ' . $host;
			$url = str_replace ( $host, $this->_ip, $url );
		}
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_HEADER, true );
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_ENCODING, 'gzip' );
		if(!is_null($this->proxyHost) && !is_null($this->proxyPort)){
			curl_setopt($ch,CURLOPT_PROXY,$this->proxyHost);
			curl_setopt($ch,CURLOPT_PROXYPORT,$this->proxyPort);
		}
		curl_setopt ( $ch, CURLOPT_TIMEOUT, $this->_timeout );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT_MS, self::$_connectTimeout );
		if ($matches ['scheme'] == 'https') {
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
		}
		if ($this->_cookie) {
			if (is_array ( $this->_cookie )) {
				curl_setopt ( $ch, CURLOPT_COOKIE, http_build_query ( $this->_cookie, '', ';' ) );
			} else {
				curl_setopt ( $ch, CURLOPT_COOKIE, $this->_cookie );
			}
		}
		if ($this->_referer) {
			curl_setopt ( $ch, CURLOPT_REFERER, $this->_referer );
		} else {
			curl_setopt ( $ch, CURLOPT_AUTOREFERER, true );
		}
		if ($this->_userAgent) {
			curl_setopt ( $ch, CURLOPT_USERAGENT, $this->_userAgent );
		} elseif (array_key_exists ( 'HTTP_USER_AGENT', $_SERVER )) {
			curl_setopt ( $ch, CURLOPT_USERAGENT, $_SERVER ['HTTP_USER_AGENT'] );
		} else {
			curl_setopt ( $ch, CURLOPT_USERAGENT, "PHP/" . PHP_VERSION . " HttpClient/1.0" );
		}
		foreach ( $this->_option as $k => $v ) {
			curl_setopt ( $ch, $k, $v );
		}

		if ($this->_header) {
			$header = [ ];
			foreach ( $this->_header as $item ) {
				// 防止有重复的header
				if (preg_match ( '#(^[^:]*):.*$#', $item, $m )) {
					$header [$m [1]] = $item;
				}
			}
			curl_setopt ( $ch, CURLOPT_HTTPHEADER, array_values ( $header ) );
		}
		// 设置POST数据
		if (isset ( $this->_postData [$url] )) {
			curl_setopt ( $ch, CURLOPT_POSTFIELDS, $this->_postData [$url] );
		}
		return $ch;
	}

	/**
	 * 支持多线程获取网页
	 *
	 * @see http://cn.php.net/manual/en/function.curl-multi-exec.php#88453
	 * @param Array/string $urls
	 * @param Int $timeout
	 * @return Array
	 */
	protected function requestUrl($urls)
	{
		// 去重
		$urls = array_unique ( $urls );
		if (! $urls)
			return [ ];
		$mh = curl_multi_init ();
		// 监听列表
		$listenerList = [ ];
		// 返回值
		$result = [ ];
		// 总列队数
		$listNum = 0;
		// 排队列表
		$multiList = [ ];
		foreach ( $urls as $url ) {
			// 创建一个curl对象
			$current = $this->_create ( $url );
			if ($this->_multiExecNum > 0 && $listNum >= $this->_multiExecNum) {
				// 加入排队列表
				$multiList [] = $url;
			} else {
				// 列队数控制
				curl_multi_add_handle ( $mh, $current );
				$listenerList [$url] = $current;
				$listNum ++;
			}
			$result [$url] = null;
			$this->_httpData [$url] = null;
		}
		unset ( $current );
		$running = null;
		// 已完成数
		$doneNum = 0;
		do {
			while ( ($execrun = curl_multi_exec ( $mh, $running )) == CURLM_CALL_MULTI_PERFORM );
			if ($execrun != CURLM_OK){
				break;
			}
			while ( true == ($done = curl_multi_info_read ( $mh )) ) {
				foreach ( $listenerList as $doneUrl => $listener ) {
					if ($listener === $done ['handle']) {
						// 获取内容
						$this->_httpData [$doneUrl] = $this->getData ( curl_multi_getcontent ( $done ['handle'] ), $done ['handle'] );

						if ($this->_httpData [$doneUrl] ['code'] != 200) {
							// \Leaps\Debug::error ( 'URL:' . $doneUrl . ' ERROR,TIME:' . $this->_httpData [$doneUrl] ['time'] . ',CODE:' . $this->_httpData [$doneUrl] ['code'] );
							$result [$doneUrl] = false;
						} else {
							// 返回内容
							$result [$doneUrl] = $this->_httpData [$doneUrl] ['data'];
							// \Leaps\Debug::info ( 'URL:' . $doneUrl . ' OK.TIME:' . $this->_httpData [$doneUrl] ['time'] );
						}
						curl_close ( $done ['handle'] );
						curl_multi_remove_handle ( $mh, $done ['handle'] );
						// 把监听列表里移除
						unset ( $listenerList [$doneUrl], $listener );
						$doneNum ++;
						// 如果还有排队列表，则继续加入
						if ($multiList) {
							// 获取列队中的一条URL
							$currentUrl = array_shift ( $multiList );
							// 创建CURL对象
							$current = $this->_create ( $currentUrl );
							// 加入到列队
							curl_multi_add_handle ( $mh, $current );
							// 更新监听列队信息
							$listenerList [$currentUrl] = $current;
							unset ( $current );
							// 更新列队数
							$listNum ++;
						}
						break;
					}
				}
			}
			if ($doneNum >= $listNum)
				break;
		} while ( true );
		// 关闭列队
		curl_multi_close ( $mh );
		return $result;
	}

	/**
	 * 获取结果数据
	 */
	public function getResutData()
	{
		return $this->_httpData;
	}

	/**
	 * 获取数据
	 *
	 * @param unknown $data
	 * @param unknown $ch
	 * @return mixed
	 */
	protected function getData($data, $ch)
	{
		$headerSize = curl_getinfo ( $ch, CURLINFO_HEADER_SIZE );
		$result ['code'] = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
		$result ['data'] = substr ( $data, $headerSize );
		$result ['header'] = explode ( "\r\n", substr ( $data, 0, $headerSize ) );
		$result ['time'] = curl_getinfo ( $ch, CURLINFO_TOTAL_TIME );
		return $result;
	}

	/**
	 * 清理设置
	 */
	protected function clearSet()
	{
		$this->_option = [ ];
		$this->_header = [ ];
		$this->_ip = null;
		$this->_files = [ ];
		$this->_cookie = null;
		$this->_referer = null;
		$this->_method = 'GET';
		$this->_postData = [ ];
	}
}