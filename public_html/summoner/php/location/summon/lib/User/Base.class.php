<?php
/**
 *@ 用户基类
 **/
class User_Base extends Base{
    protected static $recordInfo	=	array();												//用户对应的record信息 对就zy_uniqRoleRecord表
    protected static $throwSQL 		=	array();												//需要异步同步的数据
    protected static $userinfo 		=	array(); 												//角色所有信息
    protected static $updinfo 		=	array(); 												//角色更新的信息  需要同步入库
    protected static $retinfo 		=	array(); 												//返回给客户端的信息
    protected static $isupd 		=	array(); 												//判断是否需要更新用户信息
    protected static $missionNotice =	array();												//任务通知信息
    protected static $lastUpdHero 	= 	array();												//英雄最后更新信息
    protected static $lastUpdGoods	=	array('new'=>array(),'old'=>array(),'del'=>array());	//用户变化物品清单

    protected $baseTable='zy_uniqRole';															//用户角色表
    protected $baseRecordTable = 'zy_uniqRoleRecord';											//用户record信息表
    protected $heroTable = 'zy_uniqRoleHero'; 													//用户英雄表
    protected $userMissionTable='zy_uniqUserMission';											//用户任务进度表

    private $upInfo; 																			//角色升级对应参数表
    private $uinfo;	 																			//角色信息

    private $userLog;																			//用户金钱变化日志

    public function __construct( $uid='' ){
        if( empty( $uid ) ){
            $uid = (int)getReq('uid');
        }
        parent::__construct($uid);
        if( empty($this->uid) ){
            $this->log->e('uid is null');
            ret( 'uid is null' );
        }
        if( $this->uid != ADMIN_UID )
            $this->_init();
    }

    private function _init(){
        $this->redis;
        if( C('test') || !isset( self::$userinfo[$this->uid] ) || empty(self::$userinfo[$this->uid]) || !is_array( self::$userinfo[$this->uid] ) ){
            if( C('test') || !$this->redis->exists('roleinfo:'.$this->uid.':baseinfo') ){
                $this->db;
                $uinfo = $this->db->findOne($this->baseTable,'*',array( 'userid'=>$this->uid ));
                if( empty($uinfo) ){
                    $this->log->e('no user uid='.$this->uid);
                    ret('no user!',-1);
                }
                $uinfo['skey'] = md5( gettimeofday(true).rand(1000,9999) ); //登录校验
                #$uinfo['lastUpdTime'] = time(); //用户信息最后更新时间
                $uinfo['mail'] = 0; //邮件标记
                $this->redis->del('roleinfo:'.$this->uid.':baseinfo');
                $this->redis->hmset('roleinfo:'.$this->uid.':baseinfo',$uinfo);
            }else{
                $uinfo = $this->redis->hgetall('roleinfo:'.$this->uid.':baseinfo');
            }
            self::$userinfo[$this->uid] = $uinfo;
        }
        /*if( self::$userinfo[$this->uid]['_heartTime']-time() > 600 ){ #登录超时
            ret('time_out',301);
        }*/
        if( !isset( self::$updinfo[$this->uid] ) ){
            self::$updinfo[$this->uid] = array();
        }
        if( !isset( self::$recordInfo[$this->uid] ) ){
            self::$recordInfo[$this->uid] = array();
        }
        $this->uinfo = self::$userinfo[$this->uid];
    }
    /**
     *@ 根据名称查找好友信息
     **/
    function getInfo( $name ){
        $this->db;
        return $this->db->findOne( $this->baseTable,'`userid` id,`nickname`,`image`',array( 'nickname'=>$name ) );
    }
    /**
     *@ 获取角色信息
     **/
    public function getUserInfo(){
        $userinfo = self::$userinfo[$this->uid];
        $userinf['weekCode'] = $userinfo['ext1'];
        $userinf['weekCodeOverTime'] = $userinfo['ext2'];
        unset( $userinfo['ext1'] );
        unset( $userinfo['ext2'] );
        return $userinfo;
    }
    /**
     *@ 获取角色id
     **/
    public function getUid(){
        return (int)$this->uid;
    }
    /**
     *@ 获取角色所在服务器id
     **/
    public function getServerId(){
        return (int)self::$userinfo[$this->uid]['sid'];
    }
    /**
     *@ 获取角色注册服id
     **/
    public function getRid(){
        return (int)self::$userinfo[$this->uid]['rid'];
    }
    /**
     *@ 获取角色昵称
     **/
    public function getUserName(){
        return self::$userinfo[$this->uid]['nickname'];
    }
    /**
     *@ 获取角色头像
     **/
    public function getImage(){
        return self::$userinfo[$this->uid]['image'];
    }
    /**
     *@  getUserMaxLife 获取角色最大体力上限
     **/
    public function getUserMaxLife(){
        return self::$userinfo[$this->uid]['maxLife'];
    }
    /**
     *@  getUserReductTime 获取角色最大体力上限
     **/
    public function getUserReductTime(){
        return self::$userinfo[$this->uid]['lastDeductTime'];
    }
    /**
     *@ 获取角色体力值
     **/
    public function getLife(){
        return $this->_resetLife();
    }
    /**
     *@ 恢复角色体力值
     **/
    private function _resetLife(){
        $recover = RECOVER_LIFE_TIME;	//恢复一点需要时间
        $max = $this->getUserMaxLife();
        $now = time();
        $tolTime = $now - $this->getUserReductTime(); //已经恢复的总时间
        $giveLife = floor($tolTime/$recover);
        if( self::$userinfo[$this->uid]['life'] >= $max ){ //体力本身就是满的
            self::$updinfo[$this->uid]['lastDeductTime'] = self::$userinfo[$this->uid]['lastDeductTime'] = time();
            #return self::$userinfo[$this->uid]['life'];
        }elseif( ( self::$userinfo[$this->uid]['life'] + $giveLife ) > $max ){
            self::$updinfo[$this->uid]['life'] = self::$userinfo[$this->uid]['life'] = $max;
            self::$updinfo[$this->uid]['lastDeductTime'] = self::$userinfo[$this->uid]['lastDeductTime'] = time();
        }else{
            $lastTime = $this->getUserReductTime() + ( $giveLife * $recover );
            self::$updinfo[$this->uid]['life'] = self::$userinfo[$this->uid]['life'] += $giveLife;
            self::$updinfo[$this->uid]['lastDeductTime'] = self::$userinfo[$this->uid]['lastDeductTime'] = $lastTime;
        }
        $this->log->d( '* 玩家#'.$this->uid.'#的体力恢复后的体力值为->'.(int)self::$userinfo[$this->uid]['life'] );
        return (int)self::$userinfo[$this->uid]['life'];
    }
    /**
     *@ 扣除用户体力
     **/
    public function reduceLife( $nums ){
        $ulife = $this->getLife();
        if( $nums>0 && $ulife>=$nums ){
            $this->setUpdTime(3);
            $this->log->i('* 用户#'.$this->uid.'#扣除#'.$nums.'#体力'.self::$userinfo[$this->uid]['life'].'->'.( self::$userinfo[$this->uid]['life']-$nums ) );
            self::$updinfo[$this->uid]['life'] = self::$userinfo[$this->uid]['life'] = self::$userinfo[$this->uid]['life']-$nums;
            return self::$userinfo[$this->uid]['life'];
        }else{
            $this->log->e( '* 扣除玩家#'.$nums.'#'.$this->uid.'#体力失败，剩余体力#'.self::$userinfo[$this->uid]['life'] );
            return false;
        }
    }
    /**
     *@ 添加用户体力
     **/
    public function addLife( $nums ){
        $this->_resetLife();
        if( $nums > 0 ){
            $this->setUpdTime(3);
            $this->log->d('* 用户#'.$this->uid.'#添加#'.$nums.'#体力'.self::$userinfo[$this->uid]['life'].'->'.( self::$userinfo[$this->uid]['life']+$nums ) );
            self::$updinfo[$this->uid]['life'] = self::$userinfo[$this->uid]['life'] = self::$userinfo[$this->uid]['life'] +$nums;
            return self::$userinfo[$this->uid]['life'];
        }else{
            return $this->reduceLife(-$nums);
        }
    }
    /**
     *@ 获取角色指定信息的内容
     **/
    public function getTypeInfo( $type ){
        switch ( $type ) {
            case 'jewel':
            case 'cooldou':
                return $this->getCooldou();
                break;
            case 'money':
                return $this->getMoney();
            case 'mFriend':		#友情点
            case 'mAction':		#活动币
            case 'mArena':		#竞技场币
                return $this->getUserRecord( $type );
            default:
                # code...
                break;
        }
        return 0;
    }
    /**
     *@ 获取角色等级
     **/
    public function getLevel(){
        return (int)self::$userinfo[$this->uid]['level'];
    }
    /**
     *@ 获取角色当前经验
     **/
    public function getExp(){
        return (int)self::$userinfo[$this->uid]['exp'];
    }
    /**
     *@ 获取角色当前钻石数量
     **/
    public function getCooldou(){
        return (int)self::$userinfo[$this->uid]['jewel'];
    }
    /**
     *@ 获取角色当前金币数量
     **/
    public function getMoney(){
        return (string)self::$userinfo[$this->uid]['money'];
    }
    /**
     *@ 获取角色当前vip等级
     **/
    public function getVlevel(){
        return (int)self::$userinfo[$this->uid]['vlevel'];
    }
    /**
     *@ getLastAddNoSuperGoods
     **/
    public function getLastUpdGoods(){
        if( empty( self::$lastUpdGoods['old'] ) ) {
            unset(self::$lastUpdGoods['old']);
        }
        if( empty( self::$lastUpdGoods['new'] ) ) {
            unset(self::$lastUpdGoods['new']);
        }
        if( empty( self::$lastUpdGoods['del'] ) ) {
            unset(self::$lastUpdGoods['del']);
        }
        return self::$lastUpdGoods;
    }

    /**
     *@ 获取角色登录校验码
     **/
    public function getSkey(){
        return self::$userinfo[$this->uid]['skey'];
    }
    /**
     *@ 获取角色最大朋友数量
     **/
    public function getMaxFriends(){
        $fNums = self::$userinfo[$this->uid]['friends'];
        if( $this->getVlevel() > 0 ){
            $vip  = new Vip( $this->getVlevel() );
            $fNums += $vip->getExtFriends();
        }
        return (int)$fNums;
    }
    /**
     *@ getHeroMaxLevel() 获取当前召唤师等级下能召唤英雄的最高等级
     **/
    public function getHeroMaxLevel(){
        return (int)self::$userinfo[$this->uid]['maxHeroLevel'];
    }
    /**
     *@ 获取角色信息最后更新时间
     **/
    public function getLastUpdTime(){
        return (int)self::$userinfo[$this->uid]['lastUpdTime'];
    }
    /**
     *@ 获取用户的充值总金额
     **/
    public function getTotalPay(){
        return (int)self::$userinfo[$this->uid]['totalPay'];
    }
    /**
     *@ 角色是否是周卡用户
     **/
    public function isWeekCode(){
        $this->log->d( 'weekCode:'.self::$userinfo[$this->uid]['ext1'].self::$userinfo[$this->uid]['ext2'] );
        $ret = 0;
        if( self::$userinfo[$this->uid]['ext1'] > 0 && self::$userinfo[$this->uid]['ext2'] > time() ){
            $ret = 1;
        }
        return $ret;
    }
    /**
     *@ 设置用户月卡
     **/
    public function setWeekCode(){
        $this->log->d( '* 用户#'.$this->uid.'#充值周卡' );
        self::$recordInfo[$this->uid]['ext1'] = self::$retinfo[$this->uid]['weekCode'] = self::$userinfo[$this->uid]['ext1'] = 1;
        self::$recordInfo[$this->uid]['ext2'] = self::$retinfo[$this->uid]['weekCodeOverTime'] = self::$userinfo[$this->uid]['ext2'] = time() + 86400*7;
        return true;
    }
    /**
     *@ 角色是否是月卡用户
     **/
    public function isMonthCode(){
        $ret = 0;
        if( self::$userinfo[$this->uid]['monthCode'] > 0 && self::$userinfo[$this->uid]['mCodeOverTime'] > time() ){
            $ret = 1;
        }
        return $ret;
    }
    /**
     *@ 设置用户月卡
     **/
    public function setMonthCode(){
        $this->setUpdTime(3);
        $this->log->d( '* 用户#'.$this->uid.'#充值月卡' );
        self::$updinfo[$this->uid]['monthCode'] = self::$userinfo[$this->uid]['monthCode'] = 1;
        self::$updinfo[$this->uid]['mCodeOverTime'] = self::$userinfo[$this->uid]['mCodeOverTime'] = time() + 86400*30;
        return true;
    }
    /**
     *@ 获取用户卡类标记
     **/
    public function getCodeFlag(){
        $isMonth = $this->isMonthCode();
        $isWeek = $this->isWeekCode();
        if( $isMonth && $isWeek ){
            return 3;
        }
        if( $isWeek ){
            return 2;
        }
        if( $isMonth ){
            return 1;
        }
    }
    /**
     *@ 设置用户头像
     **/
    public function setUserImage( $img ){
        $this->setUpdTime(3);
        $this->log->d('* 用户#'.$this->uid.'#修改头像'.self::$userinfo[$this->uid]['image'].'->'.$img);
        self::$updinfo[$this->uid]['image'] = $img;
        return self::$userinfo[$this->uid]['image'] = $img;
    }
    /**
     *@ 设置用户最后登录时间
     **/
    public function setLoginTime( $time='' ){
        $this->setUpdTime(3);
        empty( $time ) && $time = time();
        return self::$userinfo[$this->uid]['logintime'] = self::$updinfo[$this->uid]['logintime'] = $time;
    }
    /**
     *@ 设置用户昵称 ( 不能同名 )
     **/
    public function setUserName( $name ){
        $this->setUpdTime(3);
        $this->log->d('* 用户#'.$this->uid.'#修改名称'.self::$userinfo[$this->uid]['nickname'].'->'.$name);
        self::$userinfo[$this->uid]['nickname'] = $name;
        self::$updinfo[$this->uid]['nickname'] = $name;
        return true;
    }
    /**
     *@ 设置用户登录检验码
     **/
    public function setSkey(){
        $skey = md5( gettimeofday(true).rand(1000,9999) );
        $this->log->d('* 用户#'.$this->uid.'#修改名称重新设置登录校验码'.self::$userinfo[$this->uid]['skey'].'->'.$skey);
        self::$userinfo[$this->uid]['skey'] = $skey;
        return $this->setUserHeart( 'skey', $skey );//登录校验码
    }
    /**
     *@ 设置用户私信标记
     **/
    public function setUserHeart( $tag, $val=1 ){
        $this->log->d('* 用户#'.$this->uid.'#修改心跳信息 '.$tag.'->'.$val);
        return $this->redis->hset('roleinfo:'.$this->uid.':baseinfo', $tag, $val );#self::$userinfo[$this->uid][ $tag ] = $val;
    }
    /**
     *@ 获取用户指定字段内容
     **/
    public function getUserField( $tag ){
        return $this->redis->hget('roleinfo:'.$this->uid.':baseinfo', $tag );#self::$userinfo[$this->uid][ $tag ] = $val;
    }
    /**
     *@ 设置用户私信标记
     **/
    public function setMessageFlag( $val ){
        return $this->setUserHeart( 'message', $val);
        #return $this->redis->hset('roleinfo:'.$this->uid.':baseinfo','message',$val);#return self::$userinfo[$this->uid]['message'] = $val;
    }
    /**
     *@ 设置用户邮件标记
     **/
    public function setNewMail($val=1){
        return $this->setUserHeart( 'mail', $val);
        #return $this->redis->hset('roleinfo:'.$this->uid.':baseinfo','mail',$val);#self::$userinfo[$this->uid]['mail'] = $val;
    }
    /**
     *@ 添加用户金币
     **/
    public function addMoney( $nums ){
        if( $nums > 0 ){
            $this->userLog['source'] = 1;
            $this->userLog['type'] = 1;
            $this->userLog['nums'] = $nums;

            $this->setUpdTime(2);
            $this->log->i('* 用户#'.$this->uid.'#添加#'.$nums.'#金币'.self::$userinfo[$this->uid]['money'].'->'.( self::$userinfo[$this->uid]['money']+$nums ) );
            self::$updinfo[$this->uid]['money'] = self::$userinfo[$this->uid]['money'] + $nums;
            return self::$userinfo[$this->uid]['money'] += $nums;
        }else{
            return $this->reduceMoney( -$nums );
        }
    }
    /**
     *@ 扣除用户金币
     **/
    public function reduceMoney( $nums ){
        if( $nums==0 )return true;
        $umoney = $this->getMoney();
        if( $nums>0 && $umoney>=$nums ){
            $this->userLog['source'] = 1;
            $this->userLog['type'] = 1;
            $this->userLog['nums'] = $nums;

            $this->setUpdTime(2);
            $this->log->i('* 用户#'.$this->uid.'#扣除#'.$nums.'#金币'.self::$userinfo[$this->uid]['money'].'->'.( self::$userinfo[$this->uid]['money']-$nums ) );
            self::$updinfo[$this->uid]['money'] = self::$userinfo[$this->uid]['money'] - $nums;
            return self::$userinfo[$this->uid]['money'] -= $nums;
        }else{
            $this->log->e( '* 扣除玩家#'.$this->uid.'#'.$nums.'#金币失败，剩余金币#'.self::$userinfo[$this->uid]['money'] );
            return false;
        }
    }
    /**
     *@ 添加用户钻石
     **/
    public function addCooldou( $nums ){
        if( $nums > 0 ){
            $this->userLog['source'] = 2;
            $this->userLog['type'] = 1;
            $this->userLog['nums'] = $nums;

            $this->setUpdTime(2);
            $this->log->i('* 用户#'.$this->uid.'#添加#'.$nums.'#钻石'.self::$userinfo[$this->uid]['jewel'].'->'.( self::$userinfo[$this->uid]['jewel']+$nums ) );
            self::$updinfo[$this->uid]['jewel'] = self::$userinfo[$this->uid]['jewel'] + $nums;
            return self::$userinfo[$this->uid]['jewel'] += $nums;
        }else{
            return $this->reduceCooldou( -$nums );
        }
    }
    /**
     *@ 扣除用户钻石
     **/
    public function reduceCooldou( $nums ){
        $umoney = $this->getCooldou();
        if( $nums == 0 ){return true;}
        if( $nums>0 && $umoney>=$nums ){
            $this->userLog['source'] = 2;
            $this->userLog['type'] = 1;
            $this->userLog['nums'] = $nums;

            $this->setUpdTime(2);
            $this->log->i('* 用户#'.$this->uid.'#扣除#'.$nums.'#钻石'.self::$userinfo[$this->uid]['jewel'].'->'.( self::$userinfo[$this->uid]['jewel']-$nums ) );
            self::$updinfo[$this->uid]['jewel'] = self::$userinfo[$this->uid]['jewel'] - $nums;
            return self::$userinfo[$this->uid]['jewel'] -= $nums;
        }else{
            $this->log->e( '* 扣除玩家#'.$this->uid.'#'.$nums.'#钻石失败，剩余钻石#'.self::$userinfo[$this->uid]['jewel'] );
            return false;
        }
    }
    /**
     *@ 用户充值钻石逻辑处理
     **/
    public function addTotalPay( $nums ){
        if( $nums>0 ){
            $this->setUpdTime(3);
            self::$userinfo[$this->uid]['totalPay'] += $nums;
            self::$updinfo[$this->uid]['totalPay'] = self::$userinfo[$this->uid]['totalPay'];
            $vip = new Vip( $this->getVlevel() );
            $vlevel = $vip->getVipLevelByExp( self::$userinfo[$this->uid]['totalPay'] );
            $this->log->d('可升的vip等级====>'.$vlevel.'  玩家当前的vip等级：'.$this->getVlevel());
            if( $vlevel > $this->getVlevel() ){
                $this->log->d( '* 玩家#'.$this->uid.'#vip等级升致#'.$vlevel.'#级' );
                $this->setVip( $vlevel );
            }
            $this->log->d('* 添加玩家#'.$this->uid.'#'.$nums.'充值总数为'.self::$userinfo[$this->uid]['totalPay']);
            return true;
        }else{
            return false;
        }
    }
    /**
     *@ 添加召唤师buff
     **/
    public function addRoleBuff( $buffid ){
        $buff = new Buff( $buffid );
        $this->redis->set( 'roleinfo:'.$this->uid.':buff:'.$buff->getType(), $buffid, $buff->getTime() );
        $this->log->d( '* 玩家#'.$this->uid.'#添加buff('.$buffid.')，有效时长'.$buff->getTime() );
        return array( 'overTime'=>$buff->getTime(), 'bid'=>$buffid );
    }
    /**
     *@ 获取召唤师已拥有的buff
     **/
    public function getRoleBuff(){
        $ret=array();
        $keys = $this->redis->keys('roleinfo:'.$this->uid.':buff:*');
        if( is_array( $keys ) )
            foreach( $keys as $v ){
                $buff['overTime'] = (int)$this->redis->ttl( $v );
                $buff['bid'] = (int)$this->redis->get( $v );
                $ret[] = $buff;
                unset($buff);
            }
        return $ret;
    }
    /**
     *@ 添加角色经验 升级逻辑
     **/
    public function addExp( $nums ){
        $this->upInfo = new Levelup( $this->uinfo['level'] ); //角色升级表
        $exp = $this->getExp();
        $tolexp = $exp + $nums;
        $upinfo = $this->upInfo->getUpinfo();

        if( self::$userinfo[$this->uid]['level'] >= $this->upInfo->getMaxLevel() && self::$userinfo[$this->uid]['exp'] >=  $upinfo['exp'] ){
            $this->log->e('* 召唤师#'.$this->uid.'#等级达到最大，经验已满'.$upinfo['exp']);
            #self::$updinfo[$this->uid]['exp'] = self::$userinfo[$this->uid]['exp'] =  $upinfo['exp'];
            return true;
        }

        $nextinfo = $this->upInfo->getNextUpinfo();
        while( $tolexp >= $upinfo['exp'] ){
            $tolexp = $tolexp - $upinfo['exp'];
            self::$userinfo[$this->uid]['level'] = $nextinfo['level'];
            self::$userinfo[$this->uid]['exp'] = $tolexp;
            self::$userinfo[$this->uid]['maxLife'] = $nextinfo['life'];
            self::$userinfo[$this->uid]['life'] += $nextinfo['getLife'];
            self::$userinfo[$this->uid]['friends'] = $nextinfo['friends'];
            self::$userinfo[$this->uid]['maxHeroLevel'] = $nextinfo['HeroLevel'];

            self::$updinfo[$this->uid]['level'] = $nextinfo['level'];
            self::$updinfo[$this->uid]['exp'] = $tolexp;
            self::$updinfo[$this->uid]['life'] = self::$userinfo[$this->uid]['life'];
            self::$updinfo[$this->uid]['maxLife'] = self::$userinfo[$this->uid]['maxLife'];
            self::$updinfo[$this->uid]['friends'] = $nextinfo['friends'];
            self::$updinfo[$this->uid]['maxHeroLevel'] = $nextinfo['HeroLevel'];
            $this->upInfo = new Levelup( $nextinfo['level'] );
            $upinfo = $this->upInfo->getUpinfo();
            $nextinfo = $this->upInfo->getNextUpinfo();
            $this->setMissionId(1,51);
            $this->log->d('* 用户#'.$this->uid.'#升级到 '.self::$userinfo[$this->uid]['level'].' 级，经验：'.self::$userinfo[$this->uid]['exp'].', 体力：'.self::$userinfo[$this->uid]['life'].', maxLife：'.self::$userinfo[$this->uid]['maxLife'].', getLife:'.$nextinfo['getLife']);
            if( self::$userinfo[$this->uid]['level'] >= $this->upInfo->getMaxLevel() && $tolexp >= $upinfo['exp'] ){
                self::$updinfo[$this->uid]['exp'] = self::$userinfo[$this->uid]['exp'] = $upinfo['exp'];
                $this->log->e( '* 召唤师#'.$this->uid.'#等级达到最大，经验已满->'.self::$userinfo[$this->uid]['exp'] );
                break;
            }
        }
        if( $upinfo['level'] == $this->uinfo['level'] ){
            self::$userinfo[$this->uid]['exp'] += $nums;
            self::$updinfo[$this->uid]['exp'] = self::$userinfo[$this->uid]['exp'];
        }
        $this->setUpdTime(3);
        return true;
    }
    /**
     *@ setVip() 	设置用户vip等级
     **/
    public function setVip( $vLevel ){
        $this->setUpdTime(3);
        self::$updinfo[$this->uid][ 'vlevel' ] = (int)$vLevel ;
        $this->log->i( '* 玩家#'.$this->uid.'#vip等级升致#'.$vLevel );
        return self::$userinfo[$this->uid][ 'vlevel' ] = (int)$vLevel ;
    }
    /**
     *@ setExp() 	设置用户经验
     **/
    public function setExp( $exp ){
        $this->setUpdTime(1);
        self::$updinfo[$this->uid][ 'exp' ] = $exp ;
        $this->log->i( '* 玩家#'.$this->uid.'#经验变成#'.$exp );
        return self::$userinfo[$this->uid][ 'exp' ] = $exp ;
    }
    /**
     *@ setLevel() 	设置用户等级
     **/
    public function setLevel( $level ){
        $this->setUpdTime(1);
        self::$updinfo[$this->uid][ 'level' ] = $level ;
        $this->log->i( '* 玩家#'.$this->uid.'等级#'.$level );
        return self::$userinfo[$this->uid][ 'level' ] = $level ;
    }
    /**
     *@ setMissionId() 	设置相关任务完成进度
     **/
    public function setMissionId( $type, $class, $progress=1 ){
        $proxy = $this->proxy( array('type'=>$type, 'uid'=>$this->uid, 'class'=>$class ) );
        $proxy->exec( $progress );
        return true;
    }

    /**
     *@ setMissionNotice 设置任务标记
     *	param:
     *		$type: 			大任务类型  1为系统任务  2为日常
     *		$taskClass: 	任务小类型  对应mission表中的class
     *		$config:		发生变化的任务信息
     **/
    public function setMissionNotice( $type, $taskClass, $config ){
        $this->log->i( '设置任务配置信息:'.json_encode($config) );
        if( isset( self::$missionNotice[$this->uid][$type][$taskClass]['missing'] ) && $config['missing'] > 0 && $config['missing'] < self::$missionNotice[$this->uid][$type][$taskClass]['missing'] )
            return true;
        return self::$missionNotice[$this->uid][$type][$taskClass] = $config;
    }
    /**
     *@ getMissionNotice 设置任务标记
     *	param:
     *		$type: 		任务类型  1为系统任务  2为日常
     *		$config:	发生变化的任务信息
     **/
    public function getMissionNotice(){
        $this->log->i( json_encode(self::$missionNotice) );
        $ret = '';
        if( is_array( self::$missionNotice[$this->uid] ) ){
            foreach( self::$missionNotice[$this->uid] as $k=>$v ){
                if( 1==$k ){
                    foreach( $v as $key=>$val ){
                        $set[] = $val['showMission'];
                        $set[] = $val['missing'];
                        $set[] = $val['progress'];
                        $ret[$k][$key] = implode('|',$set);
                        unset($set);
                    }
                }else{
                    foreach( $v as $key=>$val ){
                        $set[] = $val['tid'];
                        $set[] = $val['progress'];
                        $ret[$k][$key] = implode('|',$set);
                        unset($set);
                    }
                }
            }
        }
        return $ret;
        #return empty( self::$missionNotice[$this->uid] )? array():self::$missionNotice[$this->uid];
    }
    /**
     *@ setUpdTime() 设置信息更新标志
     *@ param:
     *	$flag:	标志位  为0时标记需要更新redis，1 标记需要更新心跳，2 金币或钻石数量发生变化需要插入财富流水日志库
     **/
    private function setUpdTime($flag=1){
        self::$isupd[$this->uid] = $flag;
        /*
        if( $flag > 1 ){
            self::$userinfo[$this->uid]['lastUpdTime'] = time();
        }*/
        if( $flag == 2 ){ //金币或钻石发生变化
            global $tag;
            $this->userLog['sid'] = self::$userinfo[$this->uid]['sid'];
            $this->userLog['uid'] = $this->uid;
            $this->userLog['tag'] = $tag;
            $this->userLog['time'] = date('Y-m-d H:i:s');
            $this->setThrowSQL( 'zy_statsUserLog', $this->userLog, '', 1, 'stats' );
            //$this->sdb->insert('zy_statsUserLog',$this->userLog);
        }
    }

    /**
     *@ setThrowSQL 如果信息有改动抛出sql语句后台自动同步
     **/
    public function setThrowSQL( $table, $upd, $where='', $opt='', $db='' ){
        $init['table'] = $table;
        $init['data'] = $upd;
        $init['where'] = $where;
        $init['opt'] = $opt;
        $init['db'] = $db;
        self::$throwSQL[] = $init;
        return true;
    }
    /**
     *@ getUserLastUpdInfo 获取用户信息中发生变化的那部分
     **/
    public function getUserLastUpdInfo(){
        $retinfo = self::$updinfo[$this->uid];
        if( isset( self::$retinfo[$this->uid] ) ){
            $retinfo = array_merge( $retinfo, self::$retinfo[$this->uid] );
        }
        $this->log->d('updinfo:'.json_encode( self::$updinfo[$this->uid] ));
        return $retinfo;
    }

#====== * 用户设置或同步用户zy_uniqRoleRecord表中的信息 ==========================================================
    /**
     *@ setUserRecord() 设置用户的记录表信息
     *param:
     * 	$key: 	对应 zy_uniqRoleRecord 表中的属性值
     *	$value:	$key 对应的值
     **/
    public function setUserRecord( $key, $value ){
        $this->log->d( '设置用户记录信息：'.$key.'=>'.$value );
        return self::$retinfo[$this->uid][$key] = self::$userinfo[$this->uid][$key] = self::$recordInfo[$this->uid][$key] = $value;
    }
    /**
     *@ getUserRecord() 设置用户的记录表信息
     *param:
     * 	$key: 	对应 zy_uniqRoleRecord 表中的属性值
     **/
    public function getUserRecord( $key ){
        return self::$userinfo[$this->uid][$key];
    }
    /**
     *@ addUserRecord() 添加或减少用户的记录表信息
     *param:
     * 	$key: 	对应 zy_uniqRoleRecord 表中的属性值
     *	$value:	$key 对应需要添加的值 self::$updinfo[$this->uid][$key] = 
     **/
    public function addUserRecord( $key, $value ){
        $this->log->d( '添加用户记录信息：'.$key.'+='.$value );
        return self::$retinfo[$this->uid][$key] = self::$userinfo[$this->uid][$key] = self::$recordInfo[$this->uid][$key] = (int)self::$userinfo[$this->uid][$key] + $value;
    }
    /**
     *@ setUserGuide 设置用户的新手引导进度
     **/
    function setUserGuide( $index, $gid ){
        $guide = $this->getUserRecord('guide');
        $this->log->i($guide);
        $guide = json_decode( $guide, true );
        if( is_array($guide) ){
            $guide[$index] = $gid;
        }else{
            $guide = array();
            $guide[$index] = $gid;
        }
        return $this->setUserRecord( 'guide', json_encode( $guide ) );
    }
#============================================================================================
    public function __destruct(){

    }
}
?>