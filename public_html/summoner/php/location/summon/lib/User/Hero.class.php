<?php
/**
 *@ 用户英雄类
 **/
class User_Hero extends User_Base{
	static $heroInfo=array();					//英雄信息  如果是所有信息则记录英雄列表，如果指定hid则记录当前hid对应的英雄信息
	
	protected $hid;					//英雄id
	private $hinfo;					//英雄信息

	public function __construct( $uid='',$hid='' ){
		$this->hid = (int)$hid;
		parent::__construct( $uid );
		$this->log->i('~~~~~~~~~~~~~~~~~~  User_Hero ~~~~~~~~~~~~~~~~~~');
		$this->_init();
	}

	private function _init(){
		$this->redis;
		if( empty($this->hid) ){//用户所有英雄 
			if( C('test') || !$this->redis->exists('roleinfo:'.$this->uid.':hero_checked') ){
				$this->db;
				$this->redis->hdel('roleinfo:'.$this->uid.':hero:*');
				#hid,level,exp,color,star,equip1,equip2,equip3,equip4,equip5,equip6,config
				$heros = $this->db->find($this->heroTable,'fire,hid,level,exp,color,star,equip1,equip2,equip3,equip4,equip5,equip6,config',array('uid'=>$this->uid));
				if( is_array( $heros ) )
					foreach( $heros as $v ){
						$this->redis->del( 'roleinfo:'.$this->uid.':hero:'.$v['hid'] );
						$this->redis->hmset( 'roleinfo:'.$this->uid.':hero:'.$v['hid'], $v );
					}
				$this->redis->hset('roleinfo:'.$this->uid.':hero_checked',1,get3time());
			}

			$keys = $this->redis->keys('roleinfo:'.$this->uid.':hero:*');
			if( is_array( $keys ) )
				foreach( $keys as $v ){
					$hero = $this->redis->hgetall($v);
					/*for( $i=1;$i<7; $i++ ){
						if( empty( $hero['equip'.$i] ) ){
							unset( $hero['equip'.$i] );
						}
					}*/
					$this->hinfo[] = $hero;
					unset($hero);
				}
		}else{//用户指定英雄
			if( $this->redis->exists( 'roleinfo:'.$this->uid.':hero:'.$this->hid ) ){
				self::$heroInfo[$this->uid][$this->hid] = $this->redis->hgetall( 'roleinfo:'.$this->uid.':hero:'.$this->hid );
				$this->hinfo = self::$heroInfo[$this->uid][$this->hid];
			}else{
				if( C('test') || !$this->redis->exists('roleinfo:'.$this->uid.':hero:'.$this->hid.':checked') ){
					$this->db;
					self::$heroInfo[$this->uid][$this->hid] = $this->db->findOne($this->heroTable, 'hid,level,exp,color,star,equip1,equip2,equip3,equip4,equip5,equip6,config', array( 'uid'=>$this->uid, 'hid'=>$this->hid ));
					$this->hinfo = self::$heroInfo[$this->uid][$this->hid];
				}
			}
		}
	}
/**
 *@ 删除用户内存中拥有的英雄列表
 **/
	public function delHeroInfo(){
		$this->redis->del('roleinfo:'.$this->uid.':hero_checked');
		return $this->redis->hdel( 'roleinfo:'.$this->uid.':hero:*' );
	}
/**
 *@ 获取用户拥有的英雄列表
 **/
	public function getUserHeroList(){
		$ret = array();
		foreach( $this->hinfo as $v ){
			$temp[] = $v['fire'];
			$temp[] = $v['level'];
			$temp[] = $v['exp'];
			$temp[] = $v['color'];
			$temp[] = $v['star'];
			$temp[] = $v['equip1'];
			$temp[] = $v['equip2'];
			$temp[] = $v['equip3'];
			$temp[] = $v['equip4'];
			$temp[] = $v['equip5'];
			$temp[] = $v['equip6'];
			$temp[] = $v['config'];
			$ret[ $v['hid'] ] = implode( '|', $temp );
			unset($temp);
		}
		return $ret;
	}
/**
 *@ 获取用户拥有英雄的总数
 **/
	public function getUserHeroNum(){
		return count($this->hinfo);
	}
/**
 *@ 赠送英雄 $color英雄品质（1白，2绿，3蓝，4紫，5橙）
 **/
	public function giveHero( $color='' ){
		empty( $color ) && $color = 1;
		empty( $this->hid ) && ret( 'no_hid（'.__LINE__.'）' );
		if( !empty( $this->hinfo ) && $this->hinfo['hid'] == $this->hid ){
			$nums = array( 1=>10,2=>20,3=>40,4=>80,5=>160 );
			//转成英雄碎片
			if( isset( $nums[ $color ] ) ){
				$gid = '11'.substr($this->hid,2);
				$good = new User_Goods( $this->uid, $gid );
				return $good->addGoods( $nums[ $color ] );
			}else{
				$this->log->e( ' 用户#'.$this->uid.'# 获得英雄转成碎片后因对应品质的数量找不到失败。理论上应该是在作弊！' );
				return false;
			}
		}else{ //添加英雄数量
			$this->setMissionId(1,21);
			$hero = $this->initHero($color);
			self::$heroInfo[$this->uid][$this->hid] = $hero;
			self::$lastUpdHero[$this->uid][$this->hid] = $hero;
			unset($hero['add']);
			$this->hinfo = $hero;
			return $this->redis->hmset( 'roleinfo:'.$this->uid.':hero:'.$this->hid, $hero );
		}
	}
/**
 *@ 初始化英雄数据
 **/
	private function initHero($color){
		for( $i=1;$i<=$color; $i++ ){
			$config[$i]=1;
		}
		$hero['hid'] = $this->hid;
		$hero['level'] = 1;
		$hero['exp'] = 0;
		$hero['star'] = 1;
		$hero['color'] = $color;
		$hero['uid'] = $this->uid;
		$hero['equip1'] = '0';
		$hero['equip2'] = '0';
		$hero['equip3'] = '0';
		$hero['equip4'] = '0';
		$hero['equip5'] = '0';
		$hero['equip6'] = '0';
		$hero['config'] = json_encode($config);
		$hero['add'] = 1;//$this->db->insert( $this->table, $hero );
		$this->setUpdTime();
		return $hero;
	}
/**
 *@ 添加英雄经验
 **/
	public function addHeroExp( $nums ){
		$this->log->i( '给用户#'.$this->uid.'#英雄#'.$this->hid.'#添加#'.$nums.'#经验。hLevel:'.self::$heroInfo[$this->uid][$this->hid]['level'].', exp:'.self::$heroInfo[$this->uid][$this->hid]['exp'] );
		if( empty(self::$heroInfo[$this->uid][$this->hid]) )return false;
		$hLevel = $this->getHeroMaxLevel();
		$this->upInfo = new Levelup( $this->hinfo['level'],'hero' ); //升级表
		$exp = $this->getHeroExp();
		$tolexp = $exp + $nums;
		$upinfo = $this->upInfo->getUpinfo();

		if( self::$heroInfo[$this->uid][$this->hid]['level'] >= $hLevel && self::$heroInfo[$this->uid][$this->hid]['exp'] >= $upinfo['exp'] ){
			$this->log->i('* 用户#'.$this->uid.'#升级英雄#'.$this->hid.'#已达最大等级 '.$this->getLevel().'uExp:'.self::$heroInfo[$this->uid][$this->hid]['exp'].',upExp:'.$upinfo['exp']);
			return false;
		}

		$nextinfo = $this->upInfo->getNextUpinfo();
		$flag = false;
		while( $tolexp >= $upinfo['exp'] ){
			$flag = true;
			$tolexp = $tolexp - $upinfo['exp'];
			if( $nextinfo['level'] > $this->getHeroMaxLevel() ){
				self::$heroInfo[$this->uid][$this->hid]['level'] = $this->getHeroMaxLevel();
				self::$heroInfo[$this->uid][$this->hid]['exp'] = $upinfo['exp'];
				if( 40 == self::$heroInfo[$this->uid][$this->hid]['level'] ){
					$this->setMissionId(1,26);
				}
				break;
			}else{
				self::$heroInfo[$this->uid][$this->hid]['level'] = $nextinfo['level'];
				self::$heroInfo[$this->uid][$this->hid]['exp'] = $tolexp;
			}
			if( 40 == self::$heroInfo[$this->uid][$this->hid]['level'] ){
				$this->setMissionId(1,26);
			}
			$this->upInfo = new Levelup( $nextinfo['level'],'hero' );
			$upinfo = $this->upInfo->getUpinfo();
			$nextinfo = $this->upInfo->getNextUpinfo();
			$this->log->i('* 用户#'.$this->uid.'#升级英雄#'.$this->hid.'#等级到 '.self::$heroInfo[$this->uid][$this->hid]['level'].' 级 ');
			if( self::$heroInfo[$this->uid][$this->hid]['level'] >= $hLevel && $tolexp >= $upinfo['exp'] ){
				self::$heroInfo[$this->uid][$this->hid]['exp'] = $upinfo['exp'];
				$this->log->i('* 用户#'.$this->uid.'#升级英雄#'.$this->hid.'#已达最大等级,程序结束 ');
				break;
			}
		}
		if( !$flag ){
			self::$heroInfo[$this->uid][$this->hid]['exp'] += $nums;
		}
		self::$lastUpdHero[$this->uid][$this->hid]['level'] = self::$heroInfo[$this->uid][$this->hid]['level'];
		self::$lastUpdHero[$this->uid][$this->hid]['exp'] = self::$heroInfo[$this->uid][$this->hid]['exp'];
		$this->log->i( '给用户#'.$this->uid.'#英雄#'.$this->hid.'#添加#'.$nums.'#经验。hLevel:'.self::$heroInfo[$this->uid][$this->hid]['level'].', exp:'.self::$heroInfo[$this->uid][$this->hid]['exp'] );
		$this->setUpdTime();
		return true;
	}
/**
 *@ 英雄穿装备  $index 指定英雄装备框
 **/
	function heroPutOnEquip( $index,$eqId ){
		$qConfig['g'] = $eqId;
		$eid = substr( $eqId, 0, 5 );
		$equip = new Equipbase( $eid );
		$eFire = $equip->getFire( (int)substr( $eqId, 5 ) );
		$qConfig['f'] = $eFire;
		$eqConf = json_encode($qConfig);
		self::$lastUpdHero[$this->uid][$this->hid]['equip'.$index] = $eqConf;
		$ret = self::$heroInfo[$this->uid][$this->hid]['equip'.$index] = $eqConf;
		$this->setUpdTime();
		return $ret;
	}
/**
 *@ 英雄取下装备   $index 指定英雄装备框
 **/
	function heroPutDownEquip( $index ){
		$ret = self::$heroInfo[$this->uid][$this->hid]['equip'.$index];
		self::$lastUpdHero[$this->uid][$this->hid]['equip'.$index] = '0';
		self::$heroInfo[$this->uid][$this->hid]['equip'.$index] = '0';
		$this->setUpdTime();
		return true;
	}
/**
 *@ 获取英雄指定框中的装备信息   $index 指定英雄装备框
 **/
	function getHeroEquipGid( $index ){
		$eqConf = $this->getHeroEquip($index);
		return isset( $eqConf['g'] ) ? $eqConf['g'] : '';
	}
/**
 *@ 获取英雄指定框中的装备信息   $index 指定英雄装备框
 **/
	function getHeroEquip( $index ){
		$eqConf = self::$heroInfo[$this->uid][$this->hid]['equip'.$index];
		if( empty( $eqConf ) ){
			return false;
		}
		$eqConf = json_decode($eqConf,true);
		return $eqConf;
	}
/**
 *@ 计算指定英雄的总战斗力
 **/
	function getTotalFire(){
		$heroBase = new Herobase( $this->hid );
		$heroFire = $heroBase->getFire( self::$heroInfo[$this->uid][$this->hid]['level'], self::$heroInfo[$this->uid][$this->hid]['color'],  self::$heroInfo[$this->uid][$this->hid]['config']);
		$eFire = 0;
		for( $i=1; $i<7; $i++ ){
			if( !empty( self::$heroInfo[$this->uid][$this->hid]['equip'.$i] ) ){
				$fList = json_decode( self::$heroInfo[$this->uid][$this->hid]['equip'.$i], true );
				$eFire += (int)$fList['f'];
			}
		}
		return $heroFire + $eFire;
	}
/**
 *@ 获取英雄当前经验值
 **/
	function getHeroExp(){
		return self::$heroInfo[$this->uid][$this->hid]['exp'];
	}
/**
 *@ 设置是否更新
 **/
	function setUpdTime(){
		self::$heroInfo[$this->uid][$this->hid]['fire'] = self::$lastUpdHero[$this->uid][$this->hid]['fire'] = $this->getTotalFire();
		return true;
	}
/** 
 *@ 英雄品质升级或使用灵魂石合成英雄 $level: 品质等级  1=>白  2=>绿  3=>蓝 4=>紫 5=>橙
 **/
	function colorUp( $level ){
		$this->unLockSkill( $level ); //品质升级技能解锁
		self::$lastUpdHero[$this->uid][$this->hid]['color'] = $level;
		$ret = self::$heroInfo[$this->uid][$this->hid]['color'] = $level;
		$this->setUpdTime();
		return $ret;
	}
/**
 *@ 获取当前英雄的信息
 **/
	function getHeroInfo(){
		return self::$heroInfo[$this->uid][$this->hid];
	}
/**
 *@ 获取当前英雄的等级和经验
 **/
	function getHeroLevelAndExp(){
		$ret['level'] = self::$heroInfo[$this->uid][$this->hid]['level'];
		$ret['exp'] = self::$heroInfo[$this->uid][$this->hid]['exp'];
		return $ret;
	}
/**
 *@ 获取当前英雄的等级
 **/
	function getHeroLevel(){
		return self::$heroInfo[$this->uid][$this->hid]['level'];
	}
/**
 *@ 获取当前英雄的指定技能的等级
 **/
	function getSkillLevel( $skillIndex ){
		$skillConf = $this->getSkillConfig();
		return $skillConf[ $skillIndex ];
	}
/**
 *@ 获取当前英雄的技能配置信息
 **/
	function getSkillConfig(){
		$skillConf = self::$heroInfo[$this->uid][$this->hid]['config'];
		return json_decode( $skillConf, true );
	}
/**
 *@ 获取英雄最后更新的信息
 **/
	function getLastUpdField(){
		return self::$lastUpdHero[$this->uid];
	}
/**
 *@ 英雄指定技能的升级
 **/
	function skillUp( $skillIndex ){
		$skillConf =$this->getSkillConfig();
		$skillConf[ $skillIndex ] += 1;
		$this->log->i('* 用户#'.$this->uid.'#升级英雄#'.$this->hid.'#'.$skillIndex.'技能->'.$skillConf[ $skillIndex ]);
		$this->setMissionId( 2, 27 );
		self::$lastUpdHero[$this->uid][$this->hid]['config'] = json_encode($skillConf);
		self::$heroInfo[$this->uid][$this->hid]['config'] = json_encode($skillConf);
		return $this->setUpdTime();
	}
/**
 *@ 英雄技能解锁 $skillIndex:技能下标  
 **/
	function unLockSkill( $skillIndex ){
		$skillConf = self::$heroInfo[$this->uid][$this->hid]['config'];
		$skillConf = json_decode( $skillConf, true );
		if( isset( $skillConf[ $skillIndex ] ) ){return true;}
		switch( $skillIndex ){ //技能真正解锁后品质任务加1
			case '2':$this->setMissionId(1,22);break;
			case '3':$this->setMissionId(1,23);break;
			case '4':$this->setMissionId(1,24);break;
		}
		for( $i=1; $i<=$skillIndex; $i++ ){
			if( !isset( $skillConf[ $i ] ) )
				$skillConf[ $i ] = 1;
		}
		$this->log->i('* 用户#'.$this->uid.'#英雄#'.$this->hid.'#技能（'.$this->hid.$skillIndex.'）解锁！');
		self::$lastUpdHero[$this->uid][$this->hid]['config'] = json_encode($skillConf);
		self::$heroInfo[$this->uid][$this->hid]['config'] = json_encode($skillConf);
		return $this->setUpdTime();
	}

	function __destruct(){
		
	}
}
?>