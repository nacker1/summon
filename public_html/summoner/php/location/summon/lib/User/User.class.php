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
		if( empty($uid) ) $uid = getReq('uid');
		if( empty($skey) ) $skey = getReq('skey',-1);
		$this->skey = $skey;
		parent::__construct( $uid );
		$this->log->i('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
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
		$ret[] = time();									#服务器当前时间
		$ret[] = (int)$uInfo['mail'];						#邮件标记
		$ret[] = (int)$uInfo['message'];					#私聊消息
		$ret[] = (int)$uInfo['jewel'];						#当前钻石数量
		$ret[] = $this->isMonthCode();						#是否月卡用户
		$ret[] = (int)$uInfo['invite'];						#用户好友邀请标记
		$ret[] = (int)$uInfo['vlevel'];						#用户vip等级
		$this->setUserHeart( '_heartTime', time() );		#设置用户心跳时间
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
		$recover = RECOVER_TIME;	//恢复一点需要时间
		$now = time();
		$point = array( 'point'=>$max,'lastTime'=>$now );
		if( $this->redis->exists('roleinfo:'.$this->uid.':skillPoint') ){
			$point = $this->getUserSkill();
			$tolTime = 	$now - $point['lastTime']; //已经恢复的总时间 秒
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
		$recover = RECOVER_TIME;	//恢复一点需要时间
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
		if( empty( $goods ) )return array();
		$ret = array();
		$temp = array('new'=>array(),'old'=>array());
		foreach( $goods as $k=>$v ){
			switch( $k ){
				case 'money':
					$this->addMoney($v);
					break;
				case 'life':
					$this->addLife($v);
					break;
				case 'jewel':
				case 'cooldou':
					$this->addCooldou($v);
					break;
				case 'exp':
					$this->addExp($v);
					break;
				case 'mFriend':		#友情点
				case 'mAction':		#活动币
				case 'mArena':		#竞技场币
					$this->addUserRecord( $k,$v );
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
								}								
							}
							$updGoods = $this->getLastUpdGoods();
							if( !empty( $updGoods ) ){
								$ret['list'] = $updGoods;
								$this->log->i( 'good:'.json_encode($ret) );
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
		$uInfo = $this->getUserLastUpdInfo();
		
		if( is_array( $uInfo ) ){
			$ret = array_merge( $ret, $uInfo );
		}
		return $ret;
	}
	public function __destruct(){
		# 同步用户信息
		if( isset( self::$isupd[$this->uid] ) && self::$isupd[$this->uid] > 0 ){ 
			$this->redis->hmset('roleinfo:'.$this->uid.':baseinfo',self::$userinfo[$this->uid]);
			if( self::$isupd[$this->uid] >= 2 && !empty( self::$updinfo[$this->uid] ) ){
				$this->throwSQL( $this->baseTable, self::$updinfo[$this->uid], array('userid'=>$this->uid) );
				self::$updinfo[$this->uid] = array();
			}
			self::$isupd[$this->uid] = 0;
		}
		#同步用户record信息
		if( is_array( self::$recordInfo[$this->uid] ) && !empty( self::$recordInfo[$this->uid] ) ){
			$this->redis->hmset('roleinfo:'.$this->uid.':baseinfo',self::$recordInfo[$this->uid]);
			$this->throwSQL( $this->baseRecordTable, self::$recordInfo[$this->uid], array('uid'=>$this->uid) );
			self::$recordInfo[$this->uid]=array();
		}
		
		if( !empty( self::$lastUpdHero[$this->uid] ) && count( self::$lastUpdHero[$this->uid]>0 ) ){
			$tempUpdHero = self::$lastUpdHero[$this->uid];
			self::$lastUpdHero[$this->uid] = array();
			foreach( $tempUpdHero as $hid=>$v ){
				$this->redis->hmset( 'roleinfo:'.$this->uid.':hero:'.$hid,$v );
				if( $v['add'] == 1 ){
					unset($v['add']);
					$this->setThrowSQL( $this->heroTable, $v );
				}else{
					$this->setThrowSQL( $this->heroTable,$v,array('uid'=>$this->uid,'hid'=>$hid) );
				}
			}
		}

		if( isset( self::$missionNotice[$this->uid][1] ) && count( self::$missionNotice[$this->uid][1] ) > 0 ){
			foreach( self::$missionNotice[$this->uid][1] as $k=>$v ){
				$this->setThrowSQL( $this->userMissionTable, $v, array( 'uid'=>$this->uid, 'type'=>$k ) );
			}
			unset(self::$missionNotice[$this->uid][1]);
		}

		#命令行模式启动，抛出sql语句
		if( !empty( self::$throwSQL ) && count( self::$throwSQL>0 ) ){
			$throwSQL = self::$throwSQL;
			self::$throwSQL = array();
			foreach( $throwSQL as $val ){
				$this->throwSQL( $val['table'], $val['data'], $val['where'], $val['opt'] );
			}
		}
	}
 }
?>