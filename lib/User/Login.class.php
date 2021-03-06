<?php
/**
 *@ User_User 游戏初始化类 获取召唤师信息以及自动创建角色信息
 **/
 class User_Login extends User_Base{
	private $loginLogTable = 'zy_statsUserLoginLog';				// 登录日志表
	private $roleTable='zy_uniqRole';								//用户角色表
	private $recordTable='zy_uniqRoleRecord';						//用户固定信息记录表
	private $rid;													//注册用户id
	private $sid;													//用户选择服务器id
	private $sre;													//注册号+服务器id 组成的健值对应redis 根据相应值找出 rid+sid 对应的 uid
	private $isNew=0;												//标记是否为新角色
	private $loginTime;												//此次登录时间
				
	public function __construct( $rid,$sid ){
		$this->rid = $rid;
		$this->sid = $sid;
		$this->loginTime = time();
		if( empty($this->rid)||empty($this->sid) ){
			ret('参数错误',-1);
		}
		$tid = (int)$this->rid + (int) $this->sid;
		$this->sre = Redis_Redis::init( $tid );
		$this->_init();
	}
/**
 *@ 初始化
 **/
	private function _init(){
		$this->db;
		if( C( 'test' ) || !$this->sre->exists( 'rid2uid_'.$this->rid.'_'.$this->sid ) ){
			$uinfo = $this->db->findOne($this->roleTable,' `userid` ',array( 'rid'=>$this->rid, 'sid'=>$this->sid ));
			if( empty( $uinfo ) ){
				$uinfo = $this->_createRole();
				$this->isNew = 1;
			}
			$uid = $uinfo['userid'];
			if( !empty( $uid ) )
				$this->sre->set('rid2uid_'.$this->rid.'_'.$this->sid,$uid,864000);
			else
				ret( 'db_error_'.__LINE__, -1 );
		}else{
			$uid = $this->sre->get( 'rid2uid_'.$this->rid.'_'.$this->sid );
		}
		parent::__construct( $uid );
		//初始化用户竞技场排名等信息
		if( C('test') || !$this->redis->hget( 'roleinfo:'.$this->uid.':baseinfo', 'user_record' ) ){
			$ret = $this->db->findOne( $this->recordTable, 'maxPvpTop,maxStageid,mArena,mFriend,mAction,guide,ext1,ext2,ext3,ext4', array( 'uid'=>$this->uid ) );
			if( empty( $ret ) ){
				$ret['maxPvpTop'] = 0;
				$ret['maxStageid'] = 0;
				$ret['mArena'] = 0;
				$ret['mFriend'] = 0;
				$ret['mAction'] = 0;
				$ret['guide'] = 0;
				$ret['ext1'] = 0;
				$ret['ext2'] = 0;
				$ret['ext3'] = 0;
				$ret['ext4'] = 0;
				$ret['uid'] = $this->uid;
				$this->setThrowSQL( $this->recordTable, $ret );
			}
			$ret['user_record'] = 1;
			$this->redis->hmset( 'roleinfo:'.$this->uid.':baseinfo' , $ret );
		}

		$role = new User_Rolebase( $this->rid );
		$role->setUserLastServerId( $this->sid ); //登录成功后设置用户最后登录服务器
		$this->log->d('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
	}
/**
 *@ 创建新角色
 **/
	private function _createRole(){
		if( REFUSE_REGISTER ){
			ret( ' 当前服务器人数过多，请选择其他服务器注册。 ',-1 );
		}
		$insert['nickname'] = $this->rid.$this->sid;		//昵称
		$insert['image'] = '1';								//头像
		$insert['level'] = 1;								//等级
		$insert['exp'] = 0;									//当前经验
		$insert['maxLife'] = 60;							//最大体力值
		$insert['life'] = 60;								//当前体力值
		$insert['lead'] = 0;								//领导力
		$insert['jewel'] = 0;								//钻石数量
		$insert['money'] = 10000;							//初始金币数量
		$insert['logintime'] = time();						//最后登录时间	
		$insert['lastDeductTime'] = time();					//最后扣除体力时间
		$insert['sex'] = 1;									//性别
		$insert['pageNum'] = 20;							//背包最大格数
		$insert['friends'] = 5;								//最大好友数量
		$insert['rid'] = $this->rid;						//游戏表id
		$insert['sid'] = $this->sid;						//大区id
		$insert['regtime'] = time();						//注册时间
		$i=0;
		do{
			$ret = $this->db->insert( $this->roleTable,$insert );
			$i++;
		}while(!$ret && $i<3);
		if( $ret ){
			$insert['userid'] = $ret;
			return $insert;
		}else{
			global $log;
			$log->e( 'create role error. info:'.$this->db->error().', lastSql:'.$this->db->getLastSql() );
			ret('Db error!',-1);
		}
	}
/**
 *@ 用户登录信息
 **/
	public function getUserBeginInfo(){
		$this->setSkey();
		$userinfo = $this->getUserInfo();
		$ret['new'] = $this->isNew;
		$ret['now'] = time();
		$ret['ot'] = get3unix();
		$ret['id'] = $this->getUid();
		$ret['nickname'] = $userinfo['nickname'];
		$ret['image'] = (int)$userinfo['image'];
		$ret['level'] = (int)$userinfo['level'];
		$ret['exp'] = (int)$userinfo['exp'];
		$ret['maxLife'] = (int)$userinfo['maxLife'];
		$ret['life'] = $this->getLife();
		$ret['lastDeductTime'] = (int)$this->getUserReductTime();
		$ret['jewel'] = (int)$userinfo['jewel'];
		$ret['money'] = (string)$userinfo['money'];
		$ret['vlevel'] = (int)$userinfo['vlevel'];
		$ret['monthCode'] = (int)$userinfo['monthCode'];
		$ret['mCodeOverTime'] = (int)$userinfo['mCodeOverTime'];
		$ret['sex'] = (int)$userinfo['sex'];
		$ret['pageNum'] = (int)$userinfo['pageNum'];
		$ret['maxHeroLevel'] = (int)$userinfo['maxHeroLevel'];
		$ret['skey'] = $userinfo['skey'];
		$ret['mail'] = (int)$userinfo['mail'];
		$ret['guide'] = $userinfo['guide'];																		#新手引导完成的进度id
		$ret['maxStageid'] = (int)$userinfo['maxStageid'];														#无尽之地的最大关卡id
		$ret['mFriend'] = (int)$userinfo['mFriend'];															#用户的友情点数
		$ret['mArena'] = (int)$userinfo['mArena'];																#用户的竞技场币数量
		$ret['mAction'] = (int)$userinfo['mAction'];															#用户的远征币
		$ret['buff'] = $this->getRoleBuff();																	#用户当前身上拥有的buff列表
		$ret['maxPvpTop'] = (int)$userinfo['maxPvpTop'];														#玩家pvp最高名次
		$ret['lastLoginTime'] = (int)$userinfo['logintime'];													#用户上次登录时间
		$ret['logintime'] = $this->loginTime;																	#本次登录时间
		$ret['tolPay'] = (int)$userinfo['totalPay'];															#总充值额度
		$ret['fLoginTime'] = (int)$userinfo['fLoginTime'];    #今日第一次登录时间
		$sign = new Act_Sign( $this->uid );
		$ret['sign'] = $sign->checkSign();
		if( ( (int)$userinfo['logintime'] < today3unix() && $this->loginTime >= today3unix() ) || empty( $ret['fLoginTime'] ) ){
			$ret['fLoginTime'] = $this->loginTime;
			$this->setUserHeart('fLoginTime',$this->loginTime);													#设置第一次便当时间
		}
		$this->setLoginTime( $this->loginTime );																#设置用户登录时间
		$this->setUserHeart( '_heartTime', time() );															#设置用户心跳时间
		$this->_logInfo();
		return $ret;
	}

	private function _other(){
		$tmpe = new User_User($this->uid,-1);
		if( $this->isNew ){
			$gHero = array( 10002 );
			#$gHero = array( 10001,10002,10004,10005,10006,10008,10009,10010,10011,10012,10013,10015,10018,10019,10021,10022,10023,10024,10025,10026,10027,10028,10029,10031,10032,10034,10036,10040 );
			foreach( $gHero as $v ){ 
				$hero = new User_Hero( $this->uid, $v );
				$hero->giveHero();
			}
			#封测时期注册送周卡
			if( time() > strtotime( '2015-02-02' ) && time() < strtotime( '2015-02-16' ) ){
				$this->setWeekCode();
			}
		}
	}

	private function _logInfo(){
		global $version,$channel;
		$uInfo = $this->getUserInfo();
		$insert['sid'] = $this->getServerId();
		$insert['uid'] = $this->uid;
		$insert['money'] = $uInfo['money'];
		$insert['jewel'] = $uInfo['jewel'];
		$insert['version'] = $version ? $version : '1.0.0';
		$insert['channel'] = $channel ? $channel : 1;
		$insert['time'] = date('Y-m-d H:i:s');
		$insert['isNew'] = $this->isNew;
		$insert['config'] = json_encode($uInfo);
		$this->setThrowSQL( $this->loginLogTable,$insert,'',1,'stats' );
		$this->_other();
	}

	public function __destruct(){

	}
 }
?>