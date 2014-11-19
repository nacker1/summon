<?php
class Buff extends Base{
	private $buffTable = 'zy_baseBuffConfig';					//buff配置表
	private $buffid;									//指定的buff id
	private $buffInfo;								//指定buff的信息

	function __construct( $buffid ){
		parent::__construct();
		$this->buffid = $buffid;
		$this->_init();
	}
	
	private function _init(){
		$this->pre;
		if( C('test') || !$this->pre->hget( 'baseBuffConfig:check','check' ) ){
			$this->cdb;
			$ret = $this->cdb->find( $this->buffTable, 'Buff_id bid,Buff_Type type,Buff_Value config' );
			foreach( $ret as $v ){
				$this->pre->hmset( 'baseBuffConfig:'.$v['bid'], $v );
			}
			$this->pre->hset( 'baseBuffConfig:check', 'check', 1, get3time() );
		}
		$this->buffInfo = $this->pre->hgetall( 'baseBuffConfig:'.$this->buffid );
	}

	#获取buff的类型id
	function getType(){
		return $this->buffInfo['type'];
	}

	#获取buff的有效时长
	function getTime(){
		$config = $this->getConfig();
		if( !isset( $config[ 'time' ] ) || (int)$config[ 'time' ] < 10 ){
			$config['time'] = BUFF_TIME;
		}
		return $config['time'];
	}

	#获取buff的配置信息
	function getConfig(){
		$config = json_decode( $this->buffInfo['config'], true );
		return $config;
	}
}
?>
