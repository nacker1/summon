<?php
/**
 *@ 条件统计限制类 
 *@param:
 *	 $tag: 存入redis内的标识，做好唯一性
 *   	 $uid: 用户uid 用来确定域
 *	 $times: 数据保存时长
 *	 $node: redis节点标签
 *@author <hzy@51094.com>
 *@date 2014-06-10
 *  
 **/
 class Cond extends Base{
	private $tag;			//需要作统计的标签
	private $cre;			//Cond类连接的专门redis
	private $domain;		//组装的域
	private $key;			//最终组装成的键值
	private $times; 		//组健过期时间 秒
	/**
	 *@ param:
	 *	$tag:	生成的键值标签(唯一性很强)
	 *	$uid:	用户uid  用于分端口
	 *	$times:	健值有效期
	 *	$node:	redis节点标签
	 **/
	function __construct( $tag, $uid='', $times=0, $node='Cond' ){
		$this->tag = $tag;
		$this->times = $times;
		$this->node = $node;
		parent::__construct( $uid );
		$this->_init();
	}
	
	private function _init(){
		if( !empty( $this->uid ) ){
			$tag2num = $this->uid;
			$this->domain = $this->uid.':'.$this->tag;
		}else{
			$tag2num = ord( $this->tag );
			$this->domain = $this->tag;
		}
		$redisTag = $this->node.'_'.( $tag2num%10 );
		$this->cre = Redis_Redis::initRedis( $redisTag );
	}
	/**
	 *@ 设置指定键值的值
	 **/
	public function set( $value,$key='',$times='' ){
		if( empty( $key ) ){
			$this->key = $this->domain;
		}else{
			$this->key = $this->domain.':'.$key;
		}
		$ret = $this->cre->hset($this->key,'total',json_encode($value));
		if( !empty($times)  ){
			$this->cre->expire( $this->key,$times );
		}elseif( !empty( $this->times )  && $this->cre->ttl($this->key) < 0){
			$this->cre->expire( $this->key,$this->times );
		}
		return $ret;
	}
	/**
	 *@ 获取指定键值的值
	 **/
	public function get( $key='' ){
		if( empty( $key ) ){
			$this->key = $this->domain;
		}else{
			$this->key = $this->domain.':'.$key;
		}
		$ret = $this->cre->hget( $this->key , 'total' );
		if( !empty( $ret ) ){
			return json_decode($ret,true);
		}
		return array();
	}
	public function del($key=''){
		if( empty( $key ) ){
			$this->key = $this->domain;
		}else{
			$this->key = $this->domain.':'.$key;
		}
		return $this->cre->del( $this->key );
	}
	/**
	 *@ 获取指定键值内的所有值
	 * $keys: 指定键值
	 * $flag: 标记返回的值类型，1返回时以keys中的第3个字段作下标，第4个字段作二维下标；默认下标为递填下标
	 * return: Array;
	 **/
	public function getAll( $keys='' , $flag=0 ){
		if( empty( $keys ) ){
			$keys = $this->cre->keys( $this->domain.'*' );
		}else{
			$keys = $this->cre->keys( $this->domain.':'.$keys.'*' );
		}
		$ret = array();
		if( !empty($keys) && is_array($keys) ){
			foreach( $keys as $v ){
				if( $flag==1 ){
					$t = explode(':',$v);
					if( isset($t[3]) ){
						$ret[$t[2]][$t[3]] = json_decode($this->cre->hget( $v,'total' ),true);	
					}else{
						$ret[$t[2]]['tol'] = json_decode($this->cre->hget( $v,'total' ),true);
					}
				}else{
					$ret[] = json_decode($this->cre->hget( $v,'total' ),true);
				}
			}
		}
		return $ret;
	}
	/**
	 *@ 设置指定键值的值添加$value
	 **/
	public function add( $value,$key='' ){
		$list = $this->get( $key );
		if( is_array( $value ) ){
			$list[] = $value;
		}else{
			$list = (int)$list + $value;
		}
		return $this->set($list,$key);
	}
	/**
	 *@ 设置当日次数 次日清零
	 **/
	public function setDayTimes( $value=1,$key='' ){
		if( empty( $key ) ){
			$this->key = $this->domain.':'.mktime(0,0,0);
		}else{
			$this->key = $this->domain.':'.$key.':'.mktime(0,0,0);
		}
		$ret = $this->cre->hincr($this->key,'total',$value);
		if( $this->cre->ttl( $this->key ) < 0 ){
			$ret = $this->cre->expire( $this->key,( mktime( 3,0,0 )+86400 - time() ) );
		}
		return $ret;
	}

	/**
	 *@ 获取当日次数
	 **/
	public function getDayTimes( $key='' ){
		if( empty( $key ) ){
			$this->key = $this->domain.':'.mktime(0,0,0);
		}else{
			$this->key = $this->domain.':'.$key.':'.mktime(0,0,0);
		}
		$ret = $this->cre->hget( $this->key , 'total' );
		return json_decode($ret,true);
	}

	/**
	 *@ 将当日次数清0
	 **/
	public function delDayTimes( $key='' ){
		if( empty( $key ) ){
			$this->key = $this->domain.':'.mktime(0,0,0);
		}else{
			$this->key = $this->domain.':'.$key.':'.mktime(0,0,0);
		}
		return $this->cre->hset($this->key,'total',0);
	}
 }
?>