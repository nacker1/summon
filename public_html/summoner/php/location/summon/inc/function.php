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
	function getUniqCode($len=6,$type='3',$addChars='') {
        $str ='';
        switch($type) {
            case 0:
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                break;
            case 1:
                $chars= str_repeat('0123456789',3);
                break;
            case 2:
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 3:
                $chars='abcdefghijklmnopqrstuvwxyz0123456789';
                break;
            case 4:
                $chars = "abcdefghijklmnopqrstuvwxyz";
                break;
            case 5:
                $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借";
                break;
            default :
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
                break;
        }
        if($len>10 ) {//位数过长重复字符串一定次数
            $chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5);
        }
        if($type!=4) {
            $chars   =   str_shuffle($chars);
            $str     =   substr($chars,0,$len);
        }else{
            // 中文随机字
            for($i=0;$i<$len;$i++){
              $str.= self::msubstr($chars, floor(mt_rand(0,mb_strlen($chars,'utf-8')-1)),1,'utf-8',false);
            }
        }
        return $addChars.$str;
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