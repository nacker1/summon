<?php
/**
 *@ redis»ù´¡Àà Ê¹ÓÃµ¥ÀýÄ£Ê½ 
 *@ author: < huangzy@51094.com >
 **/
 class Redis_Redis{
	private $connect;
	static $redis=array();

	private function __construct($host,$port,$pass){
		global $log;
		if( empty($host) || empty($port) ){
			gettype($log)=='object' && $log->e('Redis host or port null.£¨host:'.$host.',port:'.$port.',pass:'.$pass.'£©');
			ret('redis_class_'.__LINE__);
		}
		$this->connect = new Redis();
		if( !$this->connect->pconnect($host,$port) ){
			gettype($log)=='object' && $log->e('Redis connect fail£¨host:'.$host.',port:'.$port.',pass:'.$pass.'£©');
			ret('redis_class_'.__LINE__);
		}
		!empty($pass) && $this->connect->auth($pass);
	}
	public function initRedis($uid=''){
		return self::init($uid);
	}
	public static function init($type=''){
		dump($type);
		$con = new Config( $type );
		$redis_config = $con->getRedisList();

		#$redis_config = Config::$redis_config[Config::$env];
		if( !is_null($type) && is_numeric($type) ){
			$redname = 'redis'.($type%Config::$redis_count);
			$redisConfig = $redis_config[$redname];
		}elseif( !empty($type) ){
			$redname = $type;
			$redisConfig = $redis_config[$redname]; //¼æÈÝmatch ºÍ testÁ½¸öredis
		}
		
		if( !isset($redisConfig) || empty($redisConfig) || !is_array($redisConfig)){
			$redname = 'default';
			$redisConfig = $redis_config[$redname];
		}
		
		if( !isset(self::$redis) || !is_array(self::$redis) || empty(self::$redis) || !isset(self::$redis[$redname]) ){
			$host = $redisConfig['host'];
			$port = $redisConfig['port'];
			$pass = $redisConfig['pass'];
			self::$redis[$redname] = new Redis_Redis($host,$port,$pass);
		}
		return self::$redis[$redname];
	}

	#=============================================== keyÏà¹Ø²Ù×÷ ======================================================#
	/**
	 *@ É¾³ýÖ¸¶¨keyÖµ»òÖ¸¶¨keyµÄÒ»¸öÊý¾Ý Èç $key='name' »ò $key=array('name','age','sex') ³É¹¦·µ»Ø1 Ê§°Ü·µ»Ø0
	 **/
	public function del($keys){
		if( is_array($keys) ){
			foreach( $keys as $key ){
				$this->connect->del($key);
			}
		}else{
			$this->connect->del($keys);
		}
		return true;
	}
	/**
	 *@ É¾³ýÖ¸¶¨keyÖµ»òÖ¸¶¨keyµÄÒ»¸öÊý¾Ý Èç $key='name' »ò $key=array('name','age','sex') ³É¹¦·µ»Ø1 Ê§°Ü·µ»Ø0
	 **/
	public function hdel($keys){
		if( is_array( $keys ) ){
			foreach( $keys as $k ){
				$ks = $this->keys($k);
				foreach ($ks as $key) {
					$this->del($key);
				}
			}
		}else{
			$ks = $this->keys($keys);
			foreach ($ks as $key) {
				$this->del($key);
			}
		}
	}
	/**
	 *@ ²éÕÒ·ûºÏ¸ø¶¨Ä£Ê½µÄkey¡£
	 *	KEYS *ÃüÖÐÊý¾Ý¿âÖÐËùÓÐkey¡£
	 *	KEYS h?lloÃüÖÐhello£¬ hallo and hxlloµÈ¡£
	 *	KEYS h*lloÃüÖÐhlloºÍheeeeelloµÈ¡£
	 *	KEYS h[ae]lloÃüÖÐhelloºÍhallo£¬µ«²»ÃüÖÐhillo¡£
	 *	ÌØÊâ·ûºÅÓÃ"\"¸ô¿ª
	 **/
	public function keys($key='*'){
		return $this->connect->keys($key);
	}
	/**
	 *@ ´Óµ±Ç°Êý¾Ý¿âÖÐËæ»ú·µ»Ø(²»É¾³ý)Ò»¸ökey¡£
	 **/
	public function randomkey(){
		return $this->connect->randomkey();
	}
	/**
	 *@ ²é¿´Ö¸¶¨$keyÖµµÄÓÐÐ§ÆÚ
	 **/
	public function ttl($key){
		return $this->connect->ttl($key);
	}
	/**
	 *@ ¼ì²é¸ø¶¨keyÊÇ·ñ´æÔÚ¡£Èôkey´æÔÚ£¬·µ»Ø1£¬·ñÔò·µ»Ø0¡£
	 **/
	public function exists($key){
		return $this->connect->exists($key);
	}
	/**
	 *@ ²é¿´¹þÏ£±íkeyÖÐ£¬¸ø¶¨ÓòfieldÊÇ·ñ´æÔÚ¡££¬·µ»Ø1£¬·ñÔò·µ»Ø0¡£
	 **/
	public function hexists($key){
		if( $this->keys($key) ){
			return true;
		}
		return false;
	}
	/**
	 *@ RENAME key newkey
	 *	½«key¸ÄÃûÎªnewkey,·µ»Øtrue¡£
	 *	µ±keyºÍnewkeyÏàÍ¬»òÕßkey²»´æÔÚÊ±£¬·µ»Øfalse¡£
	 *	µ±newkeyÒÑ¾­´æÔÚÊ±£¬RENAMEÃüÁî½«¸²¸Ç¾ÉÖµ¡£
	 **/
	public function rename($key,$newkey){
		return $this->connect->rename($key,$newkey);
	}

	/**
	 *@ ·µ»ØkeyËù´¢´æµÄÖµµÄÀàÐÍ¡£
	 *	none(key²»´æÔÚ) int(0)
	 *	string(×Ö·û´®) int(1)
	 *	set(¼¯ºÏ) int(2)
	 *	list(ÁÐ±í) int(3)
	 *	zset(ÓÐÐò¼¯) int(4)
	 *	hash(¹þÏ£±í) int(5)
	 **/
	public function type($key){
		return $this->connect->type($key);
	}
		/**
	 *@ ÉèÖÃÖ¸¶¨$keyÔÚ$secondsÃëºó¹ýÆÚ
	 **/
	public function expire($key,$seconds){
		return $this->connect->expire($key,$seconds);
	}
	/**
	 *@ ÉèÖÃÖ¸¶¨$keyÔÚÖ¸¶¨ÈÕÆÚ£¨$datetime£©ºó¹ýÆÚ
	 **/
	public function expireat($key,$datetime){
		return $this->connect->expireat($key,$datetime);
	}
	/**
	 *@ ÒÆ³ý¸ø¶¨keyµÄÉú´æÊ±¼ä¡£
	 *	·µ»ØÖµ£º
	 *	µ±Éú´æÊ±¼äÒÆ³ý³É¹¦Ê±£¬·µ»Ø1.
	 *	Èç¹ûkey²»´æÔÚ»òkeyÃ»ÓÐÉèÖÃÉú´æÊ±¼ä£¬·µ»Ø0¡£
	 **/
	public function persist($key){
		return $this->connect->persist($key);
	}
	/**
	 *@ ÅÅÐò£¬·ÖÒ³µÈ
	 *	²ÎÊý $options
	 *	array(
	 *	¡®by¡¯ => ¡®some_pattern_*¡¯,
	 *	¡®limit¡¯ => array(0, 1),
	 *	¡®get¡¯ => ¡®some_other_pattern_*¡¯ or an array of patterns,
	 *	¡®sort¡¯ => ¡®asc¡¯ or ¡®desc¡¯,
	 *	¡®alpha¡¯ => TRUE,
	 *	¡®store¡¯ => ¡®external-key¡¯
	 *	)
	 *	·µ»Ø»ò±£´æ¸ø¶¨ÁÐ±í¡¢¼¯ºÏ¡¢ÓÐÐò¼¯ºÏkeyÖÐ¾­¹ýÅÅÐòµÄÔªËØ¡£
	 *	ÅÅÐòÄ¬ÈÏÒÔÊý×Ö×÷Îª¶ÔÏó£¬Öµ±»½âÊÍÎªË«¾«¶È¸¡µãÊý£¬È»ºó½øÐÐ±È½Ï¡£
	 **/
	public function sort($key,$options=array()){
		return $this->connect->sort($key,$options);
	}

	#=============================================== keyÏà¹Ø²Ù×÷ end =============================================================#

	
	#=============================================== ×Ö·û Ïà¹Ø²Ù×÷ ===============================================================#

	/**
	 *@ ÉèÖÃÒ»¸ö¼òµ¥µÄkey=>valueÖµ Èçset('mid','10')
	 **/
	public function set($key,$val,$expire=0){
		$ret = $this->connect->set($key,$val);
		if( $ret!==false && is_numeric($expire) && !empty($expire) ){
			self::expire($key,$expire);
		}
		return $ret;
	}
	/**
	 *@ ÉèÖÃÒ»¸ö¼òµ¥Êý¾Ýkey=>valueÖµ Èçmset(array('mid'=>'10','age'=>20))
	 **/
	public function mset($kvals,$expire=0){
		$ret = $this->connect->mset($kvals);
		if( $ret!==false && is_numeric($expire) && !empty($expire) ){
			foreach($kvals as $k=>$v){
				self::expire($k,$expire);
			}
		}
		return $ret;
	}
	/**
	 *@ »ñÈ¡Ò»¸öÖ¸¶¨keyµÄvalueÖµ Èçget('mid')
	 **/
	public function get($key){
		return $this->connect->get($key);
	}
	/**
	 *@ »ñÈ¡Ò»¸öÖ¸¶¨keyÖµÊý×éÖÐËùÓÐkey¶ÔÓ¦µÄvalue Èçmget(array('mid','age'))
	 **/
	public function mget($keys){
		return $this->connect->mget($keys);
	}
	#=============================================== ×Ö·û Ïà¹Ø²Ù×÷ end ===========================================================#

	/**
	 *@ ÉèÖÃÖ¸¶¨ÓòÖÐÒ»×ékey=>valueµÄÖµ Èçhmset('u:',array('mid'=>10,'kid'=>20))
	 **/
	public function hmset($domain,$kval,$expire=0){
		$ret = $this->connect->HMSET($domain,$kval) ;
		if( $ret!==false && is_numeric($expire) && !empty($expire) ){
			self::expire($domain,$expire);
		}
		return $ret===false ? false : true ;
	}
	/**
	 *@ »ñÈ¡Ö¸¶¨ÓòÖÐÒ»×ékey=>valueµÄÖµ Èçhmget('u:',array('mid','kid'))
	 **/
	public function hmget($domain,$keys){
		return $this->connect->HMGET($domain,$keys);
	}
	/**
	 *@ ÉèÖÃÖ¸¶¨ÓòÖÐÒ»¸ökey=>valueµÄÖµ Èçhget('u:','mid',10)
	 **/
	public function hset($domain,$key,$val,$expire=0){
		$ret = $this->connect->hset($domain,$key,$val);
		if( $ret!==false && $expire > 0 && is_numeric($expire) ){
			self::expire($domain,$expire);
		}
		return $ret===false ? false : true ;
	}
	/**
	 *@ »ñÈ¡Ö¸¶¨ÓòÖÐÒ»¸ökey=>valueµÄÖµ Èçhget('u:','mid')
	 **/
	public function hget($domain,$key){
		return $this->connect->hget($domain,$key);
	}
	/**
	 *@ »ñÈ¡Ö¸¶¨$domainÓòÖÐµÄËùÓÐkey=>valueÖµ Èçhgetall('u:')
	 **/
	public function hgetall($domain){
		return $this->connect->hGetAll($domain);
	}
	/**
	 *@ »ñÈ¡Ö¸¶¨$domainÓòÖÐµÄ$keyÖ¸¶¨µÄvalueµÄÖµ¼ÓÉÏ$val
	 **/
	public function hincr($domain, $key, $val){
		return $this->connect->hincrBy($domain, $key, $val);
	}
    
    public function add($domain,$key,$val,$expire=0){
        $ret = $this->connect->hSetNx($domain,$key,$val);
		if( $ret && is_numeric($expire) && $expire > 0 ){
			self::expire($domain,$expire);
		}
		return $ret ? true : false;
    }

	public function sadd($key,$val){
		return $this->connect->sadd($key,$val);
	}


	public function dbsize(){
		return $this->connect->dbSize();
	}
	
	public function lrange($key,$start=0,$end=-1){
		return $this->connect->lrange($key,$start,$end);
	}

	public function rpush($key,$val){
		return $this->connect->rpush($key,$val);
	}
	public function close(){
		$this->connect->close();
	}
 }
?>