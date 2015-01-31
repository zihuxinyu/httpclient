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

use Leaps\HttpClient\MimeType;

class Fsock
{
	/**
	 * 响应数据寄存器
	 *
	 * @var array
	 */
	protected $_httpData = [ ];

	/**
	 * User Agent 浏览器的身份标识
	 *
	 * @var string
	*/
	protected $_userAgent = '';

	/**
	 * 携带的Cookie
	 *
	 * @var string
	 */
	protected $_cookie;

	/**
	 * 页面来源
	 *
	 * @var string
	 */
	protected $_referer;
	protected $_ip;
	protected $_files = [ ];
	protected $_header = [ ];
	protected $_option = [ ];
	protected $_postData = [ ];
	protected $proxyHost = null;
	protected $proxyPort = null;
	protected $authorizationToken = null;

	/**
	 * 多列队任务进程数，0表示不限制
	 *
	 * @var int
	*/
	protected $_multiExecNum = 20;
	protected $_method = 'GET';
	protected $_timeout = 30;

	/**
	 * 构造方法
	 */
	public function __construct()
	{
	}

	/**
	 * 设置User Agent
	 *
	 * @param string $userAgent
	 * @return \Leaps\HttpClient\Adapter\Fsock
	 */
	public function setUserAgent($userAgent)
	{
		$this->_userAgent = $userAgent;
		return $this;
	}

	/**
	 * 设置
	 *
	 * @param Cookie $cookies
	 * @return \Leaps\HttpClient\Adapter\Fsock
	 */
	public function setCookie($cookies)
	{
		$this->_cookie = $cookies;
		return $this;
	}

	/**
	 * 设置认证帐户和密码
	 * @param string $username
	 * @param string $password
	 */
	public function setAuthorization($username,$password){
		$this->authorizationToken = " Basic ".base64_encode("{$username}:{$password}");
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
	 * 设置 Http Referer
	 *
	 * @param string $referer
	 * @return \Leaps\HttpClient\Adapter\Fsock
	 */
	public function setReferer($referer)
	{
		$this->_referer = $referer;
		return $this;
	}

	/**
	 * 设置IP
	 *
	 * @param string $ip
	 * @return \Leaps\HttpClient\Adapter\Fsock
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
	 * @return \Leaps\HttpClient\Adapter\Fsock
	 */
	public function setTimeout($timeout)
	{
		$this->_timeout = $timeout;
		return $this;
	}

	/**
	 * 设置Header
	 *
	 * @param array $header
	 * @return \Leaps\HttpClient\Adapter\Fsock
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
	 * @param string $value
	 * @return \Leaps\HttpClient\Adapter\Fsock
	 */
	public function setOption($key, $value)
	{
		return $this;
	}

	/**
	 * 设置多个列队默认排队数上限
	 *
	 * @param number $num
	 * @return \Leaps\HttpClient\Adapter\Fsock
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
		$this->_files [$name] = $fileName;
		return $this;
	}

	/**
	 * 设置，获取REST的类型
	 *
	 * @param string $method GET|POST|DELETE|PUT 等，不传则返回当前method
	 * @return string
	 * @return HttpClient_Driver_Fsock
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
	 * HttpClient::factory()->post($url,$data);
	 *
	 * @param $url
	 * @param string/array $vars
	 * @param $timeout 超时时间，默认120秒
	 * @return string, false on failure
	 */
	public function post($url, $vars)
	{
		// POST模式
		$this->method ( 'POST' );
		if (is_array ( $url )) {
			$myvars = [ ];
			foreach ( $url as $k => $u ) {
				if (isset ( $vars [$k] )) {
					if (is_array ( $vars [$k] )) {
						if ($this->_files) {
							// 如果需要上传文件，则不需要预先将数组转换成字符串
							$my_vars [$u] = $vars [$k];
						} else {
							$myvars [$u] = http_build_query ( $vars [$k] );
						}
					} else {
						$myvars [$u] = $vars [$k];
					}
				}
			}
		} else {
			$myvars = array ($url => $vars );
		}
		$this->_postData = $myvars;
		return $this->get ( $url );
	}

	/**
	 * PUT方式获取数据，支持多个URL
	 *
	 * @param string/array $url
	 * @param string/array $vars
	 * @param $timeout
	 * @return string, false on failure
	 */
	public function put($url, $vars)
	{
		$this->method ( 'PUT' );
		$this->_contentType = "application/x-www-form-urlencoded";
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
	 * @param $timeout
	 * @return string, false on failure
	 */
	public function delete($url)
	{
		$this->method ( 'DELETE' );
		return $this->get ( $url );
	}

	/**
	 * 创建一个CURL对象
	 *
	 * @param string $url URL地址
	 * @param int $timeout 超时时间
	 * @return fsockopen()
	 */
	protected function _create($url)
	{
		$matches = parse_url ( $url );
		$hostname = $matches ['host'];
		$uri = isset ( $matches ['path'] ) ? $matches ['path'] . (isset ( $matches ['query'] ) ? '?' . $matches ['query'] : '') : '/';
		$connPort = isset ( $matches ['port'] ) ? intval ( $matches ['port'] ) : ($matches ['scheme'] == 'https' ? 443 : 80);
		if ($matches ['scheme'] == 'https') {
			$connHost = $this->_ip ? 'tls://' . $this->_ip : 'tls://' . $hostname;
		} else {
			$connHost = $this->_ip ? $this->_ip : $hostname;
		}
		$header = [ 'Host' => $hostname,'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Connection' => 'Close' ];
		if(!is_null($this->authorizationToken)){//认证
			$header ['Authorization'] = $this->authorizationToken;
		}
		if ($this->_userAgent) {
			$header ['User-Agent'] = $this->_userAgent;
		} elseif (array_key_exists ( 'HTTP_USER_AGENT', $_SERVER )) {
			$header ['User-Agent'] = $_SERVER ['HTTP_USER_AGENT'];
		} else {
			$header ['User-Agent'] = "PHP/" . PHP_VERSION . " HttpClient/1.0";
		}
		if ($this->_referer) {
			$header ['Referer'] = $this->_referer;
		}
		if ($this->_cookie) {
			$header ['Cookie'] = is_array ( $this->_cookie ) ? http_build_query ( $this->_cookie, '', ';' ) : $this->_cookie;
		}
		if ($this->_header) {
			$header = array ();
			foreach ( $this->_header as $item ) {
				// 防止有重复的header
				if (preg_match ( '#(^[^:]*):(.*)$#', $item, $m )) {
					$header [trim ( $m [1] )] = trim ( $m [2] );
				}
			}
		}
		if ($this->_files) {
			$boundary = '----------------------------' . substr ( md5 ( microtime ( 1 ) . mt_rand () ), 0, 12 );
			$vars = "--$boundary\r\n";
			if ($this->_postData [$url]) {
				if (! is_array ( $this->_postData [$url] )) {
					parse_str ( $this->_postData [$url], $post );
				} else {
					$post = $this->_postData [$url];
				}
				// form data
				foreach ( $post as $key => $val ) {
					$vars .= "Content-Disposition: form-data; name=\"" . rawurlencode ( $key ) . "\"\r\n";
					$vars .= "Content-type:application/x-www-form-urlencoded\r\n\r\n";
					$vars .= rawurlencode ( $val ) . "\r\n";
					$vars .= "--$boundary\r\n";
				}
			}
			foreach ( $this->_files as $name => $filename ) {
				$vars .= "Content-Disposition: form-data; name=\"" . $name . "\"; filename=\"" . rawurlencode ( basename ( $filename ) ) . "\"\r\n";
				$vars .= "Content-Type: " . MimeType::getMimeType ( $filename ) . "\r\n\r\n";
				$vars .= file_get_contents ( $filename ) . "\r\n";
				$vars .= "--$boundary\r\n";
			}
			$vars .= "--\r\n\r\n";
			$header ['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;
		} else if (isset ( $this->_postData [$url] ) && $this->_postData [$url]) {
			// 设置POST数据
			$vars = is_array ( $this->_postData [$url] ) ? http_build_query ( $this->_postData [$url] ) : ( string ) $this->_postData [$url];
			$header ['Content-Type'] = 'application/x-www-form-urlencoded';
		} else {
			$vars = '';
		}
		// 设置长度
		$header ['Content-Length'] = strlen ( $vars );
		if(!is_null($this->proxyHost) && !is_null($this->proxyPort)){
			$connHost = $this->proxyHost;
			$connPort = $this->proxyPort;
			$str = $this->_method . ' ' . $url . ' HTTP/1.1' . "\r\n";
		}else{
			$str = $this->_method . ' ' . $uri . ' HTTP/1.1' . "\r\n";
		}
		foreach ( $header as $k => $v ) {
			$str .= $k . ': ' . str_replace ( array ("\r","\n" ), '', $v ) . "\r\n";
		}
		$str .= "\r\n";
		if ($this->_timeout > ini_get ( 'max_execution_time' ))
			@set_time_limit ( $this->_timeout );

		$ch = @fsockopen ($connHost, $connPort, $errno, $errstr, $this->_timeout );
		if (! $ch) {
			//\Leaps\Debug::error ( "$errstr ($errno)" );
			return false;
		} else {
			stream_set_blocking ( $ch, TRUE );
			//stream_set_timeout ( $ch, $this->_timeout );
			fwrite ( $ch, $str );
			if ($vars) {
				// 追加POST数据
				fwrite ( $ch, $vars );
			}
			return $ch;
		}
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
		// 监听列表
		$listenerList = [ ];
		// 返回值
		$result = [ ];
		// 总列队数
		$listNum = 0;
		// 记录页面跳转数据
		$redirectList = [ ];
		// 排队列表
		$multiList = [ ];
		foreach ( $urls as $url ) {
			if ($this->_multiExecNum > 0 && $listNum >= $this->_multiExecNum) {
				// 加入排队列表
				$multiList [] = $url;
			} else {
				// 列队数控制
				$listenerList [] = [ $url,$this->_create ( $url ) ];
				$listNum ++;
			}
			$result [$url] = null;
			$this->_httpData [$url] = null;
		}
		// 已完成数
		$doneNum = 0;
		while ( $listenerList ) {
			list ( $doneUrl, $ch ) = array_shift ( $listenerList );
			$time = microtime ( 1 );
			if (! $ch) {
				$result [$doneUrl] = null;
				continue;
			}
			$str = '';
			while ( true ) {
				if (feof ( $ch )) {
					break;
				}
				$str .= fgets ( $ch,4096 );
			}
			fclose ( $ch );
			$time = microtime ( 1 ) - $time;
			list ( $header, $body ) = explode ( "\r\n\r\n", $str, 2 );
			$headerArr = explode ( "\r\n", $header );
			$firstLine = array_shift ( $headerArr );
			if (preg_match ( '#^HTTP/1.1 ([0-9]+) #', $firstLine, $m )) {
				$code = $m [1];
			} else {
				$code = 0;
			}
			if (strpos ( $header, 'Transfer-Encoding: chunked' )) {
				$body = explode ( "\r\n", $body );
				$body = array_slice ( $body, 1, - 1 );
				$body = implode ( '', $body );
			}
			if (preg_match ( '#Location(?:[ ]*):([^\r]+)\r\n#Uis', $header, $m )) {
				if (isset($redirectList [$doneUrl]) && count ( $redirectList [$doneUrl] ) >= 10) {
					// 防止跳转次数太大
					$body = $header = '';
					$code = 0;
				} else {
					// 302 跳转
					$newUrl = trim ( $m [1] );
					$redirectList [$doneUrl] [] = $newUrl;
					// 插入列队
					if (preg_match ( '#Set-Cookie(?:[ ]*):([^\r+])\r\n#is', $header, $m2 )) {
						// 把cookie传递过去
						$oldCookie = $this->_cookie;
						$this->_cookie = $m2 [1];
					}
					array_unshift ( $listenerList, [ $doneUrl,$this->_create ( $newUrl ) ] );
					if (isset ( $oldCookie )) {
						$this->_cookie = $oldCookie;
					}
					continue;
				}
			}
			$rs = [ 'code' => $code,'data' => $body,'header' => $headerArr,'time' => $time ];
			$this->_httpData [$doneUrl] = $rs;
			if ($rs ['code'] != 200) {
				//\Leaps\Debug::error ( 'URL:' . $doneUrl . ' ERROR,TIME:' . $this->_httpData [$doneUrl] ['time'] . ',CODE:' . $this->_httpData [$doneUrl] ['code'] );
				$result [$doneUrl] = false;
			} else {
				//\Leaps\Debug::info ( 'URL:' . $doneUrl . ' OK.TIME:' . $this->_httpData [$doneUrl] ['time'] );
				$result [$doneUrl] = $rs ['data'];
			}
			$doneNum ++;
			if ($multiList) {
				// 获取列队中的一条URL
				$currentUrl = array_shift ( $multiList );
				// 更新监听列队信息
				$listenerList [] = [ $currentUrl,$this->_create ( $currentUrl ) ];
				// 更新列队数
				$listNum ++;
			}
			if ($doneNum >= $listNum)
				break;
		}
		return $result;
	}

	/**
	 * 获取结果数据
	 *
	 * @return multitype:
	 */
	public function getResutData()
	{
		return $this->_httpData;
	}

	/**
	 * 清理设置
	 */
	protected function clearSet()
	{
		$this->_option = [ ];
		$this->_header = [ ];
		$this->_ip = null;
		$this->_cookie = null;
		$this->_referer = null;
		$this->_postData = [ ];
		$this->_method = 'GET';
	}
}