<?php
/**
 *@ 登录校验类
 **/
 class User_User extends User_Base{
	private $skey;	//登录校验
	/**
	 *@ $uid:  角色id
	 *@ $skey: 登录校验码
	 **/
	public function __construct( $uid='',$skey='' ){
		if( empty($uid) ) $uid = getReq('uid',381440);
		if( empty($skey) ) $skey = getReq('skey',-1);
		$this->skey = $skey;
		parent::__construct( $uid );
		if( $this->skey != -1 && !C('test')){
			$this->_check(); //检验用户登录
		}
	}

	private function _check(){
		$skey = $this->getSkey();
		if( $skey != $this->skey ){
			ret('登录超时',302);
		}
	}
	/**
	 *@ 获取心跳信息
	 **/
	public function getHeartBeatInfo(){
		$uInfo = $this->getUserInfo();
		$ret['jewel'] = $uInfo['jewel'];
		$ret['now'] = time();
		return $ret;
	}
/**
 *@ 获取用户用户内在技能点信息
 **/
	public function getUserSkill(){
		$point = $this->redis->hgetall( 'roleinfo:'.$this->uid.':skillPoint' );
		foreach( $point as $k=>$v ){
			$point[$k] = (int)$v;
		}
		return $point;
	}
	/**
	 *@ 初始化接口初始化用户技能剩余点以及倒记时时间
	 **/
	public function getUserSkillInfo(){
		if( $this->getVlevel() >= 7 ){
			$max = 20;
		}else{
			$max = 10;	//最大技能点数
		}
		$recover = 60;	//恢复一点需要时间
		$now = time();
		$point = array( 'point'=>$max,'lastTime'=>$now );
		if( $this->redis->exists('roleinfo:'.$this->uid.':skillPoint') ){
			$point = $this->getUserSkill();
			$tolTime = $now - $point['lastTime']; //已经恢复的总时间 秒
			if( $tolTime >= $recover ){
				$nowPoint = $point['point'] + floor( $tolTime/$recover );
				$reduceTime = $tolTime%$recover;
				$point['point'] = $nowPoint > $max ? $max : (int)$nowPoint;
				$point['lastTime'] =(int) $now - $reduceTime;
				$this->redis->hmset( 'roleinfo:'.$this->uid.':skillPoint',$point );
			}
		}
		$point = $point;
		return $point;
	}

	/**
	 *@ 获取用户当前技能点
	 **/
	public function getUserSkillPoint(){
		if( $this->getVlevel() >= 7 ){
			$max = 20;
		}else{
			$max = 10;	//最大技能点数
		}
		$recover = 60;	//恢复一点需要时间
		$now = time();
		if( $this->redis->exists('roleinfo:'.$this->uid.':skillPoint') ){
			$userSkill = $this->getUserSkill();
			$tolTime = $now - $userSkill['lastTime']; //总恢复时间
			if( $tolTime < $recover ){
				$lastPoint = $userSkill['point'];
			}else{
				if( $userSkill['point'] < $max ){
					$nowPoint = $userSkill['point'] + floor( $tolTime/$recover );
					$reduceTime = $tolTime%$recover;
					$point['point'] = $nowPoint > $max ? $max : $nowPoint;
					$point['lastTime'] = $now - $reduceTime;
				}else{
					$point['point'] = $userSkill['point'];
					$point['lastTime'] = $now;
				}
				$this->redis->hmset( 'roleinfo:'.$this->uid.':skillPoint',$point );
				$lastPoint = $point['point'];
			}
		}else{
			$point['point'] = $max;
			$point['lastTime'] = $now;
			$this->redis->hmset( 'roleinfo:'.$this->uid.':skillPoint',$point );
			$lastPoint = $max;
		}
		$this->log->i('* 用户剩余技能点数'.$lastPoint);
		return $lastPoint;
	}
/**
 *@ 扣除用户技能点数 1 点
 **/
	public function reduceUserSkillPoint(){
		$userSkill = $this->redis->hgetall( 'roleinfo:'.$this->uid.':skillPoint' );
		if( $userSkill['point'] < 1 ){
			return false;
		}
		$this->log->i( '* 用户#'.$this->uid.'#扣除1点技能点' );
		return $this->redis->hincr( 'roleinfo:'.$this->uid.':skillPoint','point',-1 );
	}
/**
 *@ 添加用户技能点数
 **/
	public function addUserSkillPoint( $nums = 10 ){
		$this->log->i( '* 用户#'.$this->uid.'#添加'.$nums.'点技能点' );
		return $this->redis->hincr( 'roleinfo:'.$this->uid.':skillPoint', 'point', $nums );
	}
/**
 *@ 统一奖品发放 
 *@ param: 
 *  	$config  格式要求 {"money":10000,"cooldou":100,"good":"10001,1#60002,100"}  金币10000 + 钻石100 + 10001道具1个 + 60002道具100个
 **/
	public function sendGoodsFromConfig( $config ){
		if( !is_array( $config ) ){
			$goods = json_decode($config,true);
		}else{
			$goods = $config;
		}
		$ret = array();
		$temp = array('new'=>array(),'old'=>array());
		foreach( $goods as $k=>$v ){
			switch( $k ){
				case 'money':
					$this->addMoney($v);
					$uInfo = $this->getUserLastUpdInfo();
					break;
				case 'life':
					$this->addLife($v);
					$uInfo = $this->getUserLastUpdInfo();
					break;
				case 'jewel':
				case 'cooldou':
					$this->addCooldou($v);
					$uInfo = $this->getUserLastUpdInfo();
					break;
				case 'exp':
					$this->addExp($v);
					$uInfo = $this->getUserLastUpdInfo();
					break;
				case 'mFriend':		#友情点
				case 'mAction':		#活动币
				case 'mArena':		#竞技场币
					$this->addUserRecord( $k,$v );
					$uInfo = $this->getUserLastUpdInfo();
					break;
				case 'good':
					if( !empty( $v ) ){
						if( is_array( $v ) ){
							$gList = $v;
						}else{
							$gList = explode( '#',$v );
						}
						if( is_array($gList) ){
							foreach( $gList as $val ){
								$gInfo = explode(',',$val);
								if( (int)$gInfo[0]>11000 ){ #发放灵魂石
									$good = new User_Goods($this->uid,$gInfo[0]);
									$good->addGoods( $gInfo[1] );
								}else{	#发放英雄
									$hero = new User_Hero( $this->uid, $gInfo[0] );
									$hero->giveHero( $gInfo[2] );
									$good = new User_Goods( $this->uid, '11'.substr( $gInfo[0],2 ) );
								}								
							}
							if( isset($good) && gettype( $good ) == 'object' ){
								$ret['list'] = $good->getLastUpdGoods();
							}
							
							if( isset($hero) && gettype( $hero ) == 'object'  ){
								$hInfo = $hero->getLastUpdField();
								if( !empty( $hInfo ) )
									$ret['hero'] = $hInfo;
							}
						}
					}
					break;
				default:break;
			}
		}

		if( is_array( $uInfo ) ){
			$ret = array_merge( $ret, $uInfo );
		}
		return $ret;
	}
 }
?>