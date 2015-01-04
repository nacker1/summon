<?php
/**
 *@ get3unix() 获取明早3点的时间戳
 **/
	function get3unix(){
		$now = date('H');
		if( $now < 3 ){
			return mktime(3,0,0);
		}else{
			return mktime(3,0,0) + 86400;
		}
	}
/**
 *@ get3time() 获取明早3点的剩余秒数
 **/
	function get3time(){
		$now = date('H');
		if( $now < 3 ){
			return mktime(3,0,0)-time();
		}else{
			return mktime(3,0,0) + 86400 - time();
		}
	}
/**
 *@ get3time() 获取今天清空缓存时3点的时间戳
 **/
	function today3unix(){
		$now = date('H');
		if( $now < 3 ){
			return mktime(3,0,0)-86400;
		}else{
			return mktime(3,0,0);
		}
	}
/**
 *@ get15daySeconds() 获取30天后的时间秒数
 **/
	function get15daySeconds(){
		$times = strtotime( '+15 days' ) - time();
		return $times;
	}
	// 浏览器友好的变量输出
	function dump($var, $echo=true, $label=null, $strict=true) {
	    $label = ($label === null) ? '' : rtrim($label) . ' ';
	    if (!$strict) {
		if (ini_get('html_errors')) {
		    $output = print_r($var, true);
		    $output = "<pre>" . $label . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
		} else {
		    $output = $label . print_r($var, true);
		}
	    } else {
		ob_start();
		var_dump($var);
		$output = ob_get_clean();
		if (!extension_loaded('xdebug')) {
		    $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
		    $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
		}
	    }
	    if ($echo) {
		echo($output);
		return null;
	    }else
		return $output;
	}
	
	function getReq($tag,$ret=null){	
		$tval = isset($_REQUEST[$tag])&&!empty($_REQUEST[$tag])?$_REQUEST[$tag]:null;
		return is_null($tval) ? $ret : $tval;
	}

    function isAjax(){
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
            if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
                return true;
        }
        if(!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
            // 判断Ajax方式提交
            return true;
        return false;
    }

	function ret($msg='',$code=0){
		global $log;
		if( empty($msg) ){
			$msg = '操作成功';
		}
		$ret = array(
			'Ret'=>$code
		);
		if( $code == 0 && !is_array($msg) ){
			$msg = array('msg'=>$msg);
		}
		$code==0 ? $ret['data']=$msg : $ret['desc']=$msg;
		$retString = msgpack_pack($ret);
		echo $retString;
		if( gettype($log)=='object' ){
			$times = gettimeofday(true) - C('start');
			$log->d(json_encode($ret));
			$error = "接口调用结束【{$times}秒】包长=".strlen($retString)."<>".strlen(json_encode($ret))."\n";
			if( $code != 0 ){
				$error = $msg.'。'.$error;
				$log->e($error);
			}else{
				$log->f($error);
			}
		}
		exit;
	}
	
	/**
	 * 获取和设置配置参数 支持批量定义
	 * @param string|array $name 配置变量
	 * @param mixed $value 配置值
	 * @return mixed
	 */
	function C($name=null, $value=null) {
		static $_config = array();
		// 无参数时获取所有
		if (empty($name)) {
			if(!empty($value) && $array = S('c_'.$value)) {
				$_config = array_merge($_config, array_change_key_case($array));
			}
			return $_config;
		}
		// 优先执行设置获取或赋值
		if (is_string($name)) {
			if (!strpos($name, '.')) {
				$name = strtolower($name);
				if (is_null($value))
					return isset($_config[$name]) ? $_config[$name] : null;
				$_config[$name] = $value;
				return;
			}
			// 二维数组设置和获取支持
			$name = explode('.', $name);
			$name[0]   =  strtolower($name[0]);
			if (is_null($value))
				return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
			$_config[$name[0]][$name[1]] = $value;
			return;
		}
		// 批量设置
		if (is_array($name)){
			$_config = array_merge($_config, array_change_key_case($name));
			if(!empty($value)) {// 保存配置值
				S('c_'.$value,$_config);
			}
			return;
		}
		return null; // 避免非法参数
	}


	function isLucky($rate){//返回是否是指定概率之内
		$max = 1000000;
		$rand = rand(1,$max);
		if($rand < $max*$rate)return true;
		return false;
	}
	
	function retRate($rates=array(10=>0.02,9=>0.03,8=>0.03,7=>0.03,6=>0.04,5=>0.05,4=>0.05,3=>0.05,2=>0.2,1=>0.5)){
		if( count( $rates ) < 1 ){
			return -1;
		}
		$tolrate = array_sum($rates);
		if( $tolrate < 1 ){
			$rates[] = 1 - $tolrate;
			asort($rates);
		}
		$max = 100000000;
		$rand = mt_rand(1,$max);
		$temp=1;
		foreach($rates as $k=>$v){
			$temp -= $v;
			if( $rand > $temp*$max ){
				return $k;
			}
		}
		return count($rates)-1;
	}
	
	function getIp(){
		if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ) {
	        		$ips = preg_split('/,\s*/', trim($_SERVER["HTTP_X_FORWARDED_FOR"]));
			return ip2long($ips[0]);
	    	} elseif ( isset($_SERVER["HTTP_X_REAL_IP"]) ) {
	            	return ip2long($_SERVER["HTTP_X_REAL_IP"]);
		}
	    	return ip2long($_SERVER["REMOTE_ADDR"]);
	}

	/**
	 *@ autoCreateDir 自动创建目录
	 **/
	 function autoCreateDir($path){
		if( is_dir($path) ) return true;
		$dirname = dirname($path);
		if ( !is_dir($dirname) ){
			autoCreateDir($dirname);
		}
		!is_writeable($dirname) && die('Error：'.$dirname.' can\' be write !!');
        @mkdir($path,0777) or die('mkdir fail:'.$path);
        return chmod($path,0777);
	}

/** 
 * 把一个汉字转为unicode的通用函数，不依赖任何库，和别的自定义函数，但有条件 
 * 条件：本文件以及函数的输入参数应该用utf-8编码，不然要加函数转换 
 * 其实亦可轻易编写反向转换的函数，甚至不局限于汉字，奇怪为什么PHP没有现成函数 
 * @author xieye 
 * 
 * @param {string} $word 必须是一个汉字，或代表汉字的一个数组(用str_split切割过) 
 * @return {string} 一个十进制unicode码，如4f60，代表汉字 “你” 
 * 
 * @example 
 *   echo "你 ".getUnicodeFromOneUTF8("你"); 
 *   echo "<br />"; 
 *   echo "好 ".getUnicodeFromOneUTF8("好"); 
 *   echo "<br />"; 
 *   echo "你好 ".getUnicodeFromOneUTF8("你好"); 
 *   echo "<br />"; 
 *   echo "你好吗 ".getUnicodeFromOneUTF8("你好吗"); 
 *   你 20320 
 *   好 22909 
 *   你好 251503099357000 
 *   你好吗 4.21952182258E+21 
 */  
	function getUnicodeFromOneUTF8($word) {  
		if (is_array( $word))  
			$arr = $word;  
		else  
			$arr = str_split($word);  
		$bin_str = '';  
		foreach ($arr as $value)  
			$bin_str .= decbin(ord($value));  
		$bin_str = preg_replace('/^.{4}(.{4}).{2}(.{6}).{2}(.{6})$/','$1$2$3', $bin_str);  
		return bindec($bin_str);  
	}

	/**
     * 字符串截取，支持中文和其他编码
     * @static
     * @access public
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符
     * @return string
     */
    function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
        if(function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif(function_exists('iconv_substr')) {
            $slice = iconv_substr($str,$start,$length,$charset);
        }else{
            $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("",array_slice($match[0], $start, $length));
        }
		if(strlen($slice)<$length)return $slice;
			return $suffix ? $slice.'...' : $slice;
    }
/**
 *@ getUniqCode 生成指定长度的唯一字符串
 **/
	function getUniqCode( $length = 10 ){
		return uniqid(rand(1, 100000));
	}
/**
* 可以统计中文字符串长度的函数
*/
	function abslength($str)
	{
	    $len=strlen($str);
	    $i=0;
	    $length = 0;
	    while($i<$len)
	    {
	        if(preg_match("/^[".chr(0xa1)."-".chr(0xff)."]+$/",$str[$i]))
	        {
	            $i+=3;
	        }
	        else
	        {
	            $i+=1;
	        }
	        $length++;
	    }
	    return $length;
	} 
	function str2dechex( $str ){
		for( $i=0;$i<strlen($str);$i++ ){
			$str .= dechex(ord($str[$i])).' ';
		}
		return $str;
	}