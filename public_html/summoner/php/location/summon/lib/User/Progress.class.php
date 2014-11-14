<?php
/**
 *@ 用户关卡进度类
 **/
class User_Progress extends User_Base{
	private $progress_table = 'zy_uniqRoleProgress';				//用户关卡进度表
	private $pList;									//用户关卡进度列表
	private $type;									//关卡类型  1普通， 2精英 ， 3炼狱
	private $cid;									//关卡id

	function __construct( $cid='' ){
		parent::__construct();
		$this->log->i('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
		$this->type=substr($cid,1,1);
		$this->cid=$cid;
		$this->_init();
	}

	private function _init(){
		$this->redis;
		if( C('test')  || !$this->redis->hget( 'role:'.$this->uid.':progress_check', 'checked' ) ){
			$this->db;
			$ret = $this->db->find( $this->progress_table, 'cId,star,type', array( 'uid'=>$this->uid ), 'order by star desc' );
			if( is_array($ret) )
				foreach( $ret as $v ){
					$this->redis->hmset( 'role:'.$this->uid.':progress:'.$v['type'].':'.$v['cId'], $v );
				}
			$this->redis->hset( 'role:'.$this->uid.':progress_check', 'checked', 1, get15daySeconds() ); //数据保存15天
		}
	}
/**
 *@ 获取用户的所有关卡进度列表
 **/
	public function getUserProgressList(){
		$keys = $this->redis->keys( 'role:'.$this->uid.':progress:*' );
		$ret = array();
		if( is_array( $keys ) )
			foreach( $keys as $v ){
				$val = $this->redis->hgetall( $v );
				$temp[] = $val['cId'];
				$temp[] = $val['star'];
				$temp[] = $val['type'];
				array_push($ret, $temp);
				unset($temp);
			}
		return $ret;
	}
/**
 *@ 设置用户的关卡进度
 *@param
 *	$star:	通关星级
 **/
	public function setUserProgress( $star ){
		$tProgress = $this->redis->hgetall( 'role:'.$this->uid.':progress:'.$this->type.':'.$this->cid );
		if( empty( $tProgress ) ){
			$insert['cId'] = $this->cid;
			$insert['star'] = $star;
			$insert['time'] = time();
			$insert['type'] = $this->type;
			$insert['uid'] = $this->getUid();
			$this->redis->hmset( 'role:'.$this->uid.':progress:'.$this->type.':'.$this->cid, $insert );
			$this->setThrowSQL( $this->progress_table, $insert ); 
		}else{
			if( $tProgress['star'] < $star ){
				$this->setThrowSQL( $this->progress_table, array('star'=>$star), array( 'id'=>$tProgress['id'] ) ); 
				$this->redis->hset( 'role:'.$this->uid.':progress:'.$this->type.':'.$this->cid, 'star', $star );
			}
		}
		return true;
	}
}
?>