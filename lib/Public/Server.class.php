<?php
/**
 *@ Server 服务器类
 **/
 class Server extends Pbase{
	private $table = 'zy_baseServerList'; 		//服务器列表表名
	private $slist = array();		  		//服务器列表
	private $updtime;				  //服务器最后更新时间
	private $newServer;				  //最新大区id

	private $sid;	//指定服务器id

	public function __construct( $sid='' ){
		parent::__construct();
		if( !empty( $sid ) ){
			$this->sid = $sid;
		}
		$this->_init();
	}

	private function _init(){
		$ret;
		if( empty( $this->sid ) ){ //初始化所有服务器列表
			if( C( 'test' ) || !$this->pre->exists( 'server:list:1' ) ){
				$this->cdb;
				$slist = $this->cdb->find($this->table);
				foreach( $slist as $v ){
					$this->pre->hmset( 'server:list:'.$v['id'],$v,86400 );
					$ret[$v['id']] = $v;
					if( empty($this->updtime) || $this->updtime < $v['updtime'] ){
						$this->updtime = $v['updtime'];
					}
					if( empty($this->newServer) || $this->newServer < $v['id'] ){
						$this->newServer = $v['id'];
					}
					if( !$this->pre->exists('server:status:'.$v['id']) ){
						$stats = array('stats'=>1,'close'=>0,'cInfo'=>'');
						$this->pre->hmset('server:status:'.$v['id'],$stats);
					}
				}
			}else{
				$skeys = $this->pre->keys('server:list:*');
				foreach( $skeys as $v ){
					$sinfo = $this->pre->hgetall($v);
					$ret[ $sinfo['id'] ] = $sinfo;
					if( empty($this->newServer) || $this->newServer < $sinfo['id'] ){
						$this->newServer = $sinfo['id'];
					}
					if( empty($this->updtime) || $this->updtime < $sinfo['updtime'] ){
						$this->updtime = $sinfo['updtime'];
					}
				}
			}
			$this->slist = $ret;
		}else{ //初始化指定id服务器
			if( C( 'test' ) || !$this->pre->hexists( 'server:list:'.$this->sid ) ){
				$this->cdb;
				$slist = $this->cdb->findOne($this->table,'*',array('id'=>$this->sid));
				$this->pre->hmset( 'server:list:'.$slist['id'],$slist,86400 );
				if( !$this->pre->exists('server:status:'.$slist['id']) ){
					$stats = array('stats'=>1,'close'=>0,'cInfo'=>'');
					$this->pre->hmset('server:status:'.$slist['id'],$stats);
				}
			}else{
				$slist = $this->pre->hgetall('server:list:'.$this->sid);
			}
			$this->slist = $slist;
		}
	}
/**
 *@ 获取服务器列表
 **/
	public function getServerList(){
		return $this->slist;
	}

	public function getServersStatus(){
		if( empty($this->sid) ){
			foreach( $this->slist as $v ){
				$stats = $this->pre->hgetall('server:status:'.$v['id']);
				if( empty($stats) ){
					$stats['stats'] = 1;
					$stats['close'] = 0;
					$stats['cInfo'] = '';
				}
				$ret[ $v['id'] ] = $stats;	
			}
		}else{
			$stats = $this->pre->hgetall('server:status:'.$this->sid);
			if( empty($stats) ){
				$stats['stats'] = 1;
				$stats['close'] = 0;
				$stats['cInfo'] = '';
			}
			$ret = $stats;	
		}
		
		return $ret;
	}

	public function getLastUpdTime(){
		return $this->updtime;
	}

/**
 *@ 获取最新大区id
 **/
	public function getNewServerId(){ 
		return $this->newServer;
	}
/**
 *@ 获取服务器列表
 **/
	public function setTop(){ //暂时删除redis数据
		$this->pre->hdel('server:list:*');
	}
/**
 *@ 关闭服务器
 **/
	public function stopServer( $str='' ){
		return $this->pre->hmset('server:status:'.$this->sid,array('close'=>1,'cInfo'=>$str));
	}
/**
 *@ 开启服务器
 **/
	public function startServer(){
		return $this->pre->hmset('server:status:'.$this->sid,array('close'=>0,'cInfo'=>''));
	}
/**
 *@ 更新或添加服务器信息
 **/
	public function update( $config ){
		$this->cdb;
		if( !empty($this->sid) ){
			if( $this->pre->exists('server:list:'.$this->sid) )
				$this->pre->hmset('server:list:'.$this->sid,$config);
			return $this->cdb->update( $this->table, $config, array( 'id'=>$this->sid ) ) );
		}else{
			return $this->cdb->insert( $this->table, $config );
		}				
	}
 }
?>