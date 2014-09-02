<?php
/**
 *@ 初始化
 **/
class Base{
	static protected $syncData;		//同步的数据
	protected $log;			//日志
	protected $uid;			//用户uid

	public function __construct($uid=''){
		global $log;
		if( gettype($log) == 'object' ){
			$this->log = $log;
		}else{
			$this->log = new Logger('sync');
		}
		if( !empty($uid) ){
			$this->uid = (int)$uid;
		}
	}

	public function __invoke(){
		var_dump($this);
	}

	public function __call($name,$arguments){
		ret( $name.' method no exists!',-1 );
	}

	public function __get( $param ){
		switch( $param ){
			case 'redis': //存储用户信息的redis
				$this->redis = Redis_Redis::init($this->uid);	
				break;
			case 'pre': //存储配置信息的redis
				$this->pre = Redis_Redis::init();break;
			case 'cdb': //存储配置信息的db
				$this->cdb = Db_Mysql::init('config');break;
			case 'db': //存储当前服务器用户盯着信息的db
				$this->db = Db_Mysql::init();break;
			case 'sdb'://统计数据db
				$this->sdb = Db_Mysql::init('stats');break;
			case 'adb'://活动库db
				$this->adb = Db_Mysql::init('action');break;
		}
	}
	/**
	 *@ 根据概率值$rate看是能获奖
	 *@ rate 概率从小至大排
	 **/
	public function retRate($rates=array(10=>0.02,9=>0.03,8=>0.03,7=>0.03,6=>0.04,5=>0.05,4=>0.05,3=>0.05,2=>0.2,1=>0.5)){
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
		return -1;
	}

	function __destruct(){
		if( !empty( self::$syncData ) && is_array( self::$syncData ) ){
			$temp = self::$syncData;
			self::$syncData = '';
			$com = 'php /data/web/summon/syncDb.php -s \''.serialize($temp).'\' &'; 
			$this->log->i($com);
			if( PHP_OS == 'Linux' )
				@pclose(popen( $com,'r' ));
		}
	}
}
?>