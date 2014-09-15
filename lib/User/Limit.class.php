<?php
/**
 *@ User_Limit  用户资格次数限制通用类  每日3点清空
 **/
class User_Limit extends User_Base{
	private $table='zy_baseDayLimitConfig';			//限制表
	private $tag ;							//限制类型 如： giveLifeDay 每日赠送体力次数限制，
	private $cond;							//限制类连接的redis源
	private $tolLimit;						//当前标签的总参与次数
	private $freeTimes;						//当前标签当日的免费使用次数
	private $limitConfig;						//限制的配置信息

	/**
	 *@ param:
	 *	$tag: 	用户限制类型  
	 **/
	public function __construct( $tag,$domain='dayLimit',$time='' ){
		parent::__construct();
		$this->log->i('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
		$this->tag = $tag;
		$this->flag = $tag;
		if( !empty( $domain ) ){
			$this->tag = $domain.':'.$this->tag;
		}
		if( empty( $this->tag ) ){
			ret( 'no_tag' );
		}
		if( empty($time) ){
			$time= get3time();
		}
		$this->cond = new Cond( $this->tag, $this->uid, $time );
		$this->_init();
	}

	private function _init(){
		$this->pre;
		if( C('test') || !$this->pre->exists('userLimit:'.$this->flag) || ! $this->pre->hget( 'userLimit:'.$this->flag.'_check' , 'check' )){
			$this->cdb;
			$ret = $this->cdb->findOne( $this->table,'tag,times,vip,expend,give,timeLimit,rule,freeTime', array( 'tag'=>$this->flag ));
			if( empty( $ret ) ){
				ret( $this->flag.'_config_null',-1);
			}
			$this->pre->hmset( 'userLimit:'.$this->flag,$ret );
			$this->pre->hset( 'userLimit:'.$this->flag.'_check' , 'check' , 1 );
			$this->pre->expire( 'userLimit:'.$this->flag.'_check',86400 );
		}
		$this->freeTimes = (int)$this->pre->hget('userLimit:'.$this->flag,'freeTime');
		$this->tolLimit = (int)$this->pre->hget('userLimit:'.$this->flag,'times');
		$vipTag = $this->pre->hget('userLimit:'.$this->flag,'vip');
		if( !empty( $vipTag ) && $this->getVlevel() > 0 ){
			$vip = new Vip( $this->getVlevel() );
			$ext = $vip->getTagValue( $vipTag );
			if( !empty( $ext ) ){
				$this->tolLimit += $ext;
			}
		}
		$this->limitConfig = $this->pre->hgetall( 'userLimit:'.$this->flag );
	}
/**
 *@ 检测操作的时间间隔
 **/
	private function checkTimeLimit( $key='' ){
		if( $this->getTimeLimit( $key ) ){
			if( $this->getExpend() > 0 ){ //时间限制内是否支持购买行为，如果支持则基数大于0
				return $this->getExpend();
			}else{
				ret( 'timeLimit',-1 );
			}
		}
		return 0;
	}
/**
 *@ 添加今日已使用次数
 **/
	public function addLimitTimes( $nums=1,$key='' ){
		if( $this->getLastFreeTimes($key) > 0 && $this->getTimeLimit( $key ) ) return true;
		$used = $this->getUsedTimes( $key );
		$nums += $used;
		$this->setTimeLimit( $key );
		return $this->cond->set( $nums , $key );
	}
/**
 *@ getUsedTimes()  获取当前已使用的次数
 **/
	public function getUsedTimes( $key='' ){
		return (int)$this->cond->get($key);
	}
/**
 *@ 获取用户当日此标签中的剩余次数
 **/
	public function getLastTimes( $key='' ){
		$used = $this->getUsedTimes( $key );
		return (int)( $this->tolLimit - $used );
	}
/**
 *@ 获取用户当日此标签中的剩余次数
 **/
	public function getLastFreeTimes( $key='' ){
		$used = $this->getUsedTimes( $key );
		return (int)( $this->freeTimes - $used );
	}
/**
 *@ 获取当前标签的消耗基数值
 **/
	public function getExpend(){
		return (int)$this->limitConfig['expend'];
	}
/**
 *@ 获取当前标签一次性获得数量
 **/
	public function getGiveNum(){
		return (int)$this->limitConfig['give'];
	}
/**
 *@ 获取当前标签购买时扣钻是否需要规则
 **/
	public function getRule(){
		return (int)$this->limitConfig['rule'];
	}
/**
 *@ 设置每次数使用的时间间隔
 **/
	private function setTimeLimit( $key='' ){
		$times = (int)$this->limitConfig['timeLimit'];
		if( empty( $times ) ){
			return true;
		}
		return $this->cond->set( time(), $key.'_timeLimit',$times );
	}
/**
 *@ 获取免费次数使用的时间间隔
 **/
	private function getTimeLimit( $key='' ){
		return (int)$this->cond->get( $key.'_timeLimit' );
	}
/**
 *@ delTimeLimit 清空限制记录
 **/
	public function delTimeLimit( $key='' ){
		return $this->cond->del( $key.'_timeLimit' );
	}
/**
 *@ delLimit 清空限制记录
 **/
	public function delLimit( $key='' ){
		return $this->cond->del( $key );
	}
/**
 *@ 获取当前标签购买需要的钻石数量
 **/
	public function getOneTimeCooldou( $key='' ){
		$freeTimes = $this->getLastFreeTimes($key);
		$this->log->i( 'freeTimes:'.$freeTimes );
		
		if( $freeTimes > 0 ){ //免费操作时间  作时间间隔判断
			return $this->checkTimeLimit( $key );
		}else{
			$used = $this->getUsedTimes( $key );
			$this->log->i( 'usedTimes:'.$used );
			$buyTimes = $used - $this->freeTimes;
			if( $this->getRule() ){
				$rate = array( 1=>1, 2=>1, 3=>2, 4=>2, 5=>4, 6=>4, 7=>8 ); //第7次封顶
				$time = $buyTimes + 1;
				if( $time>7 ){
					$time = 7;
				}
				$expend = $this->getExpend();
				return $rate[$time] * $expend;
			}else{
				return $this->getExpend();
			}
		}
	}
}
?>