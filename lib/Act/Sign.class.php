<?php
/**
 *@ 每日签到类
 **/
class Act_Sign extends User_Base{
	private $table='zy_baseSignConfig';	//签到配置表
	private $month;			//当月缩写
	private $tolDay;				//累计签到天数
	private $cond;				//cond 对应的redis连接源
	private $overTime;			//过期时间
	private $userSigninfo;			//当前用户的签到信息

	function __construct(){
		parent::__construct();
		$this->pre;
		$month = $this->pre->hget( 'action:sign:month_checked','month');
		$this->month = !empty( $month ) ? $month : (int) date('m');
		$this->overTime = mktime(3,0,0,($this->month+1),1)-time(); //签到过期时间为下月1号早上3点
		$this->cond = new Cond( 'actionSign_'.$this->month, $this->uid, $this->overTime );
		$this->_init();
	}
/**
 *@ 每日签到数据初始化
 **/
	private function _init(){
		/**
		 *@ 拉取签到奖品配置信息
		 **/
		if( C('test') || !$this->pre->exists('action:sign:month:1') || !$this->pre->hget('action:sign:month_checked','check') ){
			$this->adb;
			$this->pre->hdel('action:sign:month:*');
			$ret = $this->adb->find( $this->table,'*',array( 'Sign_Month'=>$this->month ) );
			if( empty( $ret ) ){
				$this->log->e( '本月（'.$this->month.'月）签到未配置' );
				#ret( 'no_config_'.$this->month );
				$ret = $this->adb->find( $this->table,'*',array( 'Sign_Month'=>0 ) );	//默认配置
			}
			foreach( $ret as $v ){
				$this->pre->hmset( 'action:sign:month:'.$v['Sign_Day'],$v );
			}
			$this->pre->hset( 'action:sign:month_checked','check',1 );
			$this->pre->hset( 'action:sign:month_checked','month',$this->month );
			$this->pre->expire( 'action:sign:month_checked',$this->overTime  ); //签到过期时间为下月第一天的 3：00：00
		}
	}
/**
 *@ 清除签到相关数据
 **/
	function delCache(){
		$this->pre->del('action:sign:month_checked');
		$this->cond->delDayTimes('common');
		$this->cond->delDayTimes('vip');
	}
/**
 *@ 拉取每日签到配置信息
 **/
	public function getSignConfig( $month ){
		if( $month != $this->month ){
			$keys = $this->pre->keys( 'action:sign:month:*' );
			$ret = array();
			foreach( $keys as $v ){
				$ret['list'][] = $this->pre->hgetall( $v );
			}
		}
		$ret['tol'] = $this->getTotalTimes();
		$ret['com']= $this->getCommonTimes();
		$ret['vip'] = $this->getVipTimes();
		$ret['month'] = (int)$this->month;
		return $ret;
	}
/**
 *@ 获取用户当日签到次数
 **/
	public function getCommonTimes(){
		return (int)$this->cond->getDayTimes('common');
	}
/**
 *@ 获取vip用户当日签到次数
 **/
	public function getVipTimes(){
		return (int)$this->cond->getDayTimes('vip');
	}
/**
 *@ 获取用户本月累计签到次数
 **/
	public function getTotalTimes(){
		return (int)$this->cond->get('total');
	}
/**
 *@ 执行签到动作
 **/
	public function signIn(){
		$signInfo = $this->cond->get('total');
		if( empty( $signInfo ) ){
			$total = 1;
		}else{
			$total = $signInfo+1;
		}
		$daySign = $this->getCommonTimes();
		$vipSign =$this->getVipTimes();
		$this->log->i( '用户#'.$this->uid.'#今日签到次数：com->'.$daySign.' & vip->'.$vipSign );
		if( $daySign>0 && $vipSign>0 ){
			return false;
		}
		$dayConfig = $this->pre->hgetall( 'action:sign:month:'.$total );
		$addNums = $dayConfig['Item_Num'];
		$add = false;
		/*if( $dayConfig['Item_Id'] > 11000 )
			$good = new User_Goods( $this->uid, $dayConfig['Item_Id'] );*/
		if( empty($daySign) ){//普通签到物品领取
			$this->log->i('* 每日签到普通用户物品发放');
			switch ( $dayConfig['Item_Id'] ) {
				case '1':
					# code...
					$add['money'] += $addNums;
					#$this->addMoney( $addNums  );
					break;
				case '2':
					$add['cooldou'] += $addNums;
					#$this->addCooldou( $addNums  );
					break;
				default:
					$give[] = $dayConfig['Item_Id'].','.$addNums;
					/*$good->addGoods( $addNums  );
					$ret = $good->getLastUpdGoods();*/
					break;
			}
			#$this->setMissionId(1,62);
			$this->cond->set( $total,'total' );
			$this->cond->setDayTimes(1,'common');	
		}
		if( !empty($dayConfig['Double_NeedVip']) ){
			if( empty($vipSign)  && $this->getVlevel() >=  $dayConfig['Double_NeedVip'] ){//vip用户达到要求再奖励一次
				$this->log->i('* 每日签到（vip'.$dayConfig['Double_NeedVip'].'及以上） 双倍奖励发放');
				switch ( $dayConfig['Item_Id'] ) {
					case '1':
						# code...
						$add['money'] += $addNums;
						#$this->addMoney( $addNums  );
						break;
					case '2':
						$add['cooldou'] += $addNums;
						#$this->addCooldou( $addNums  );
						break;
					default:
						$give[] = $dayConfig['Item_Id'].','.$addNums;
						/*$good->addGoods( $addNums  );
						$ret = $good->getLastUpdGoods();*/
						break;
				}
				$this->cond->setDayTimes(1,'vip');
			}
		}else{
			$this->cond->setDayTimes(1,'vip');
		}
		
		$this->log->i( 'add:'.json_encode($add) );

		if( isset( $give ) )
			$add['good'] = implode('#',$give);
		return $add;
	}
}
?>