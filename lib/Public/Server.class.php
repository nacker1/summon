<?php
/**
 *@ Server 服务器类
 **/
 class Server extends Pbase{
	private $table = 'zy_baseServerList'; 					//服务器列表表名
	private $slist = array();		  						//服务器列表
	private $updtime;				  						//服务器最后更新时间
	private $newServer;				  						//最新大区id

	private $sid;											//指定服务器id

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
			if( C( 'test' ) || !$this->pre->exists( 'server:list_check' ) ){
				$this->cdb;$this->preMaster;$this->pre=$this->preMaster;
				$this->preMaster->hdel( 'server:list:*' );
				$slist = $this->cdb->find($this->table);
				foreach( $slist as $v ){
					$this->preMaster->hmset( 'server:list:'.$v['id'],$v,get3time() );
					$ret[$v['id']] = $v;
					if( empty($this->updtime) || $this->updtime < $v['updtime'] ){
						$this->updtime = $v['updtime'];
					}
					if( empty($this->newServer) || $this->newServer < $v['id'] ){
						$this->newServer = $v['id'];
					}
					if( C( 'test' ) || !$this->pre->exists('server:status:'.$v['id']) ){
						$stats = array('stats'=>2,'cInfo'=>'');
						$this->preMaster->hmset('server:status:'.$v['id'],$stats);
					}
				}
				$this->preMaster->set( 'server:list_check', 1, get3time() );
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
			if( C( 'test' ) || !$this->pre->exists( 'server:list:'.$this->sid ) ){
				$this->cdb;$this->preMaster;$this->pre=$this->preMaster;
				$slist = $this->cdb->findOne($this->table,'*',array('id'=>$this->sid));
				$this->preMaster->hmset( 'server:list:'.$slist['id'],$slist,get3time() );
				if( C( 'test' ) || !$this->pre->exists('server:status:'.$slist['id']) ){
					$stats = array('stats'=>2,'cInfo'=>'');
					$this->preMaster->hmset('server:status:'.$slist['id'],$stats);
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
		$ret = array();
		foreach( $this->slist as $v ){
			if( empty( $v['name'] ) ){continue;}
			$temp[] = $v['name'];
			$temp[] = $v['tcp'];
			$temp[] = $v['php'];
			$temp[] = $v['top'];
			$ret[ $v['id'] ] = $temp;
			unset($temp);
		}
		return $ret;
	}
/**
 *@ 获取服务器完整列表
 **/
	public function getServerAllList(){
		#dump( $this->cdb->getConfig() );
		$ret = array();
		foreach( $this->slist as $v ){
			$ret[] = $v;
		}
		return $ret;
	}
/**
 *@ 获取服务器信息
 **/
	public function getServerInfo(){
		return $this->slist;
	}
/**
 *@ 获取游戏最新版本信息
 **/
	public function getServerVer(){
		$retConf = $this->pre->hgetall( 'summon:zy_baseGameConfig' );
		if( empty( $ret ) ){
			$this->cdb;
			$ret = $this->cdb->findOne( 'zy_baseGameConfig', 'config', array( 'tag'=>'game_base_version_admin' ) );
			if( !isset( $ret['config'] ) ){
				$ret['config'] = '{"version":1,"url":"http://summon.51094.com/download","size":"10001"}';
			}
			$this->pre->set( 'summon:zy_baseGameConfig', $ret['config'], get3time() );	
			$retConf = $ret['config'];
		}
		return json_decode( $retConf, true );
	}
/**
 *@ 获取服务器php请求地址
 **/
	public function getServerPhpUrl(){
		return $this->slist['php'];
	}
/**
 *@ 获取指定区的DB配置信息		指定sid
 **/
	public function getDbList(){
		if( empty( $this->sid ) || !is_numeric( $this->sid ) )return array();
		$dbConf = json_decode( $this->slist['dbConf'], true );
		$ret = array();
		if(is_array( $dbConf ))
			foreach( $dbConf as $v ){
				$tag = $v['tag'];
				$ret[ $tag ] = $v;
			}
		return $ret;
	}
/**
 *@ 获取指定区的Redis配置信息		指定sid
 **/
	public function getRedisList(){
		if( empty( $this->sid ) || !is_numeric( $this->sid ) )return array();
		$conf = json_decode( $this->slist['redisConf'], true );
		$ret = array();
		if(is_array( $conf ))
			foreach( $conf as $v ){
				$tag = $v['tag'];
				$ret[ $tag ] = $v;
			}
		return $ret;
	}
	public function getServersStatus(){
		if( empty($this->sid) ){
			foreach( $this->slist as $v ){
				$stats = $this->pre->hgetall('server:status:'.$v['id']);
				if( empty($stats) ){
					$temp[] = 2;
					$temp[] = '';
				}else{
					$temp[] = (int)$stats['stats'];
					$temp[] = (int)$stats['cInfo'];
				}
				$ret[ $v['id'] ] = $temp;	
				unset($temp);
			}
		}else{
			$stats = $this->pre->hgetall('server:status:'.$this->sid);
			if( empty($stats) ){
				$temp[] = 1;
				$temp[] = '';
			}else{
				$temp[] = (int)$stats['stats'];
				$temp[] = (int)$stats['cInfo'];
			}
			$ret = $temp;	
			unset($temp);
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
		$this->preMaster;$this->pre=$this->preMaster;
		$this->preMaster->del( 'server:list_check' );
		$this->preMaster->hdel('server:list:*');
	}
/**
 *@ 关闭服务器   stats=> 1:关闭  2:空闲  3:繁忙 4：爆满
 **/
	public function stopServer( $str='' ){
		$this->preMaster;$this->pre=$this->preMaster;
		return $this->preMaster->hmset('server:status:'.$this->sid,array('stats'=>1,'cInfo'=>$str));
	}
/**
 *@ 开启服务器
 **/
	public function startServer(){
		$this->preMaster;$this->pre=$this->preMaster;
		return $this->preMaster->hmset('server:status:'.$this->sid,array('stats'=>2,'cInfo'=>''));
	}
/**
 *@ 设置服务器状态
 **/
	public function setServerStart( $val ){
		$this->preMaster;$this->pre=$this->preMaster;
		return $this->preMaster->hmset('server:status:'.$this->sid,array('stats'=>$val,'cInfo'=>''));
	}
/**
 *@ 更新或添加服务器信息
 **/
	public function update( $config ){
		if( empty( $config['name'] )  || empty( $config['tcp'] ) || empty( $config['php'] ) || empty( $config['max'] ) ){ return false; }
		$config['updtime'] = time();
		$this->cdb;$this->preMaster;$this->pre=$this->preMaster;
		$this->setTop();
		$this->preMaster->hdel('server:list:*');
		if( !empty($this->sid) ){
			$ret = $this->cdb->update( $this->table, $config, array( 'id'=>$this->sid ) );
		}else{
			$ret = $this->cdb->insert( $this->table, $config );
		}		
		if( !$ret ){
			$this->log->e( 'LastSql:'.$this->cdb->getLastSql() );
		}
		return $ret;
	}
 }
?>