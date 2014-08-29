<?php
/* ***************************************
*	curl�?用于像另一个程序发送数据，并继续原页面的运�?
*	运行所需参数如下
*	$data[userid]="hello2221";   //参数1.$data[xxx]:数据数组
*	$data[name]="1121";  
*	$data[mobile]="19212311";  
*	$aim_url='http://127.0.0.1/b.php';  //2.接受数据并单独处理的页面
*	$curl_p = new curl();  
*	$post = @$curl_p->post($aim_url, $data);//这里是b.php的访问地址，请自行修改  
*	$get = @$curl_p->get($aim_url);得到数据
*************************************************/
class Curl {
	private $url; //所要请求的url地址

	function __construct( $url ) {
		$this->url = $url;
		return true;
	}
	function execute($method, $fields = '', $userAgent = '', $httpHeaders = '', $username = '', $password = '') {
		$ch = curl::create();
		if (false === $ch) {
			return false;
		}
		if (is_string($this->url) && strlen($this->url)) {
			$ret = curl_setopt($ch, CURLOPT_URL, $this->url);
		} else {
			return false;
		} //是否显示头部信息  
		curl_setopt($ch, CURLOPT_HEADER, false); //  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($username != '') {
			curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
		}
		$method = strtolower($method);
		if ('post' == $method) {
			curl_setopt($ch, CURLOPT_POST, true);
			if (is_array($fields)) {
				$sets = array();
				foreach($fields AS $key =>$val){
					$sets[] = $key.'='.urlencode($val);
				}
				$fields = implode('&', $sets);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		} else if ('put' == $method) {
			curl_setopt($ch, CURLOPT_PUT, true);
		} //curl_setopt($ch, CURLOPT_PROGRESS, true);  
		//curl_setopt($ch, CURLOPT_VERBOSE, true);  
		//curl_setopt($ch, CURLOPT_MUTE, false);  
		curl_setopt($ch, CURLOPT_TIMEOUT, 20); //设置curl超时秒数，例如将信息POST出去3秒钟后自动结束运行�? 
		if (strlen($userAgent)) {
			curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		}
		if (is_array($httpHeaders)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
		}
		$ret = curl_exec($ch);
		if (curl_errno($ch)) {
			$ret = array(0=>curl_error($ch),1=>curl_errno($ch));
			curl_close($ch);
			return $ret;
		} else {
			curl_close($ch);
			if (!is_string($ret) || !strlen($ret)) {
				return false;
			}
			return $ret;
		}
	}
	function post($fields, $userAgent = '', $httpHeaders = '', $username = '', $password = '') {
		$ret = curl::execute('POST', $fields, $userAgent, $httpHeaders, $username, $password);
		if (false === $ret) {
			return false;
		}
		if( is_array($ret) ){
			return false;
		}
		return $ret;
	}
	function get( $userAgent = '', $httpHeaders = '', $username = '', $password = '') {
		$ret = curl::execute('GET', '', $userAgent, $httpHeaders, $username, $password);
		if (false === $ret) {
			return false;
		}
		if (is_array($ret)) {
			return false;
		}
		return $ret;
	}
	function create() {
		$ch = null;
		if (!function_exists('curl_init')) {
			return false;
		}
		$ch = curl_init();
		if (!is_resource($ch)) {
			return false;
		}
		return $ch;
	}
}

?>
