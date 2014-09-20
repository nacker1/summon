<?php
/**
 *@ 召唤师背包物品
 **/
 class User_Goods extends User_Base{
	private $table='zy_uniqRoleGoods'; 							//用户物品表
	private $gid;										//物品id
	private $ugid;										//用户拥有物品id  对应roolgoods表中的id
	private $type;										//物品类型
	private $goodinfo;									//物品清单   所有物品时存储所有物品是个数据   指定类型时存储指定类型的所有物品  指定物品存储指定物品信息
	private static $lastUpdGoods=array('new'=>array(),'old'=>array(),'del'=>array());	//用户变化物品清单
	private $bgood;									//物品基本配置类

	public function __construct( $uid,$gid='',$ugid='' ){
		parent::__construct($uid);
		$this->log->i('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
		$this->gid = (int)$gid;
		$this->ugid = (int)$ugid;
		$this->type = (int)substr($gid,0,1);
		if( 3 == $this->type ){
			if( strlen( $this->gid ) == 5 ){
				$this->gid *= 100;
			}
		}
		if( !empty( $this->gid ) ){
			$this->bgood = new Goodbase( $this->gid );
		}
		$this->_init();
	}

	private function _init(){
		$this->redis;
		if( !empty( $this->gid ) && !empty( $this->type ) ){ //指定物品
			if( 1==$this->bgood->getGoodSuper() ){
				$keys = 'roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid;
			}else{
				$keys = 'roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid.':'.$this->ugid;
			}
			if( $this->redis->exists( $keys ) ){
				$this->goodinfo = $this->redis->hgetall( $keys );
			}
		}elseif( empty( $this->gid ) && !empty( $this->type ) ){ //指定类型的所有物品
			if( C('test') || $this->redis->hexists( 'roleinfo:'.$this->uid.':goods:'.$this->type.':*' ) ){
				$this->redis->hdel('roleinfo:'.$this->uid.':goods:'.$this->type.':*');
				$keys = $this->redis->keys('roleinfo:'.$this->uid.':goods:'.$this->type.':*');
				foreach( $keys as $v ){
					$this->goodinfo[] = $this->redis->hgetall( $v );
				}
			}
		}else{//用户所有物品
			if( !C('test') ){
				$keys = $this->redis->keys('roleinfo:'.$this->uid.':goods:*');
				foreach( $keys as $v ){
					$g = explode( ':',$v );
					if( count($g)<5 || !is_numeric( $g[3] ) ){
						continue;
					}else{
						$this->goodinfo[$g[3]][] = $this->redis->hgetall( $v );
					}
				}
			}
			if( C('test') || ( empty( $this->goodinfo ) && !$this->redis->get('roleinfo:'.$this->uid.':goods:check') ) ){ //每天检测一次
				$this->db;
				$goodbase = new Goodbase();
				$gsuper = $goodbase->getGoodsSuper();
				$goods = $this->db->find($this->table,'id,gid,nums,gtype',array('uid'=>$this->uid));
				if( is_array( $goods ) ){
					$this->redis->hdel('roleinfo:'.$this->uid.':goods:*');
					foreach( $goods as $v ){
						$set['gid'] = $v['gid'];
						$set['nums'] = $v['nums'];
						$set['gtype'] = $v['gtype'];
						if( $gsuper[$v['gid']] ){
							$this->redis->hmset( 'roleinfo:'.$this->uid.':goods:'.$v['gtype'].':'.$v['gid'], $set );
						}else{
							$set['id'] = $v['id'];
							$this->redis->hmset( 'roleinfo:'.$this->uid.':goods:'.$v['gtype'].':'.$v['gid'].':'.$v['id'], $set );
						}
						$this->goodinfo[$v['gtype']][] = $set;
					}
				}
				$this->redis->set( 'roleinfo:'.$this->uid.':goods:check', 1 );
				$this->redis->expire( 'roleinfo:'.$this->uid.':goods:check', 86400 );
			}
		}
	}
/**
 *@ 获取用户所有物品信息
 **/
	public function getAllGoods(){
		$ret = array();
		if( !empty($this->goodinfo) ){
			foreach( $this->goodinfo as $val ){
				foreach( $val as $v ){
					#$t[] = $v['gid'];
					#$t[] = $v['nums'];
					$ret[] = $v;#implode('|', $t);
					#unset($t);
				}
			}
		}
		return $ret;
	}
/**
 *@ 添加用户物品  物品同步入库时无id的物品按插入操作
 **/
	public function addGoods( $nums=1 ){
		if( $nums < 1 ){return $this->reduceGoods( -$nums );}
		
		if( $this->bgood->getGoodSuper() == 1 ){ //可重叠物品计算数量
			if( $this->redis->exists( 'roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid ) ){
				$this->goodinfo['nums'] += $nums;
				self::$lastUpdGoods['old'][$this->gid]=$this->goodinfo['nums'];
				$this->log->i('* 给用户('.$this->uid.')发放#'.$nums.'#个物品（'.$this->type.' -> '.$this->gid.'）成功');
				$this->setThrowSQL( $this->table,array( 'nums'=>$this->goodinfo['nums'] ),array(  'uid'=>$this->uid,'gid'=>$this->gid ) );
				return $this->redis->hincr( 'roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid, 'nums', $nums);
			}else{
				$insert['gid'] = $this->gid;
				$insert['nums'] = $nums;
				$insert['gtype'] = $this->type;
				$insert['uid'] = $this->uid;
				$this->setThrowSQL( $this->table, $insert );
				$this->log->i('* 给用户('.$this->uid.')发放#'.$nums.'#个物品（'.$this->type.' -> '.$this->gid.'）成功');
				$this->goodinfo = $insert;
				unset($insert['uid']);
				self::$lastUpdGoods['new'][] = $insert;
				$this->redis->hmset('roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid,$insert);
				return true;
				/*$insert['id'] = $this->db->insert($this->table,$insert);

				if( $insert['id'] ){
					self::$lastUpdGoods['new'][] = $insert;
					$this->goodinfo = $insert;
					if( $this->redis->hmset('roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid,$insert) ){
						$this->log->i('* 给用户('.$this->uid.')发放#'.$nums.'#个物品（'.$this->type.' -> '.$this->gid.'）成功');
						return true;
					}else{
						$this->log->e('* 发放#'.$nums.'#个物品（'.$this->type.' -> '.$this->gid.'）失败。写redis失败');
						return false;
					}
				}else{//发放失败
					$this->log->e('* 发放#'.$nums.'#个物品（'.$this->type.' -> '.$this->gid.'）失败，插入id='.$insert['id'].'。数据入库失败'.$this->db->error());
					return false;
				}*/
			}
		}else{	
			if( empty($this->ugid) && !$this->redis->exists('roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid.':'.$this->ugid) ){
				for( $i=0;$i<$nums;$i++ ){ 
					switch( $this->bgood->getColor() ){
						case '3':$this->setMissionId(1,33);break;
						case '4':$this->setMissionId(1,34);break;
						case '5':$this->setMissionId(1,35);break;
					}
					unset($insert);
					$insert['gid'] = $this->gid;
					$insert['nums'] = 1;
					$insert['gtype'] = $this->type;
					$insert['uid'] = $this->uid;
					$this->db;
					$insert['id'] = $this->db->insert($this->table,$insert);
					if( $insert['id'] ){
						$this->goodinfo = $insert;
						$this->ugid = $insert['id'];
						self::$lastUpdGoods['new'][] = $insert;
						if( $this->redis->hmset('roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid.':'.$this->ugid,$insert) ){
							$this->log->i('* 给用户('.$this->uid.')发放#1#个物品（'.$this->type.' -> '.$this->gid.'）成功');
						}else{
							$this->log->e('* 发放#1#个物品（'.$this->type.' -> '.$this->gid.'）失败。写redis失败');
						}
					}else{//发放失败
						$this->log->e('* 发放#1#个物品（'.$this->type.' -> '.$this->gid.'）失败，插入id='.$insert['id'].'。数据入库失败，错误信息 '.$this->db->error());
					}
				}
				return true;
			}else{
				self::$lastUpdGoods['old'][$this->gid][]=array( 'ugid'=>$this->ugid,'nums'=>1 );
				$this->setThrowSQL( $this->table,array( 'nums'=>1 ),array(  'uid'=>$this->uid,'gid'=>$this->gid ) );
				return $this->redis->hincr('roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid.':'.$this->ugid,'nums',1); //脱装备时用

			}
		}
	}

/**
 *@ 消耗用户物品  物品同步入库时无id的物品按插入操作
 **/
	public function reduceGoods( $nums=1, $opt='' ){
		$ugood = (int)$this->getGoodsNum();
		if( $nums < 1 || $ugood < $nums ){
			$this->log->e('* 物品（'.$this->gid.'）不足无法扣除（剩余：'.$ugood.' )）');
			return false;
		}

		if( $this->bgood->getGoodSuper() == 1 ){ //可重叠物品计算数量
			if( $this->redis->hincr( 'roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid, 'nums' ,-$nums ) !==false ){
				$this->goodinfo['nums'] -= $nums;
				self::$lastUpdGoods['old'][$this->gid]=$this->goodinfo['nums'];
				$this->log->e('* 扣除用户('.$this->uid.') #'.$nums.'#个物品（'.$this->type.' -> '.$this->gid.'）成功');
				$this->setThrowSQL( $this->table,array( 'nums'=>($ugood-$nums) ),array(  'uid'=>$this->uid,'gid'=>$this->gid ) );
				return true;
			}else{//错误日志  回收。
				$this->log->e('* 扣除用户('.$this->uid.') #'.$nums.'#个物品（'.$this->type.' -> '.$this->gid.'）失败');
				return false;
			}
		}else{
			if( $opt == 'del' ){ //出售物品做特殊处理
				$this->redis->del( 'roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid.':'.$this->ugid );
				$this->setThrowSQL( $this->table,'',array( 'id'=>$this->ugid ) );
				self::$lastUpdGoods['del'][]=$this->ugid;
			}else{
				$this->redis->hincr( 'roleinfo:'.$this->uid.':goods:'.$this->type.':'.$this->gid.':'.$this->ugid, 'nums' ,-$nums );
				$this->goodinfo['nums'] -= $nums;
				if( !empty( $this->ugid ) && $this->bgood->getGoodSuper()==0 ){
					self::$lastUpdGoods['old'][$this->gid][]=array( 'ugid'=>$this->ugid,'nums'=>0 );
				}else{
					self::$lastUpdGoods['old'][$this->gid]=$this->goodinfo['nums'];
				}
				$this->setThrowSQL( $this->table,array( 'nums'=>($ugood-$nums) ),array(  'uid'=>$this->uid,'gid'=>$this->gid ) );
			}
			$this->log->e('* 扣除用户('.$this->uid.') #'.$nums.'#个物品（'.$this->type.' -> '.$this->gid.'）成功');
			return true;
		}
	}

/**
 *@ 物品是否支持叠加
 **/
	public function isSuper(){
		return $this->bgood->getGoodSuper();
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
 *@ 获取用户指定物品数量
 **/
	public function getGoodsNum(){
		if( isset( $this->goodinfo['nums'] ) ){
			return $this->goodinfo['nums'];
		}
		return 0;
	}
/**
 *@ 获取指定物品类型
 **/
	public function getType(){
		return $this->type;
	}
/**
 *@ 获取指定物品名称
 **/
	public function getGoodName(){
		return $this->bgood->getGoodName();
	}
/**
 *@ 获取指定物品提供的能量点
 **/
	public function getValue(){
		return $this->bgood->getGoodConfig();
	}
 }
?>