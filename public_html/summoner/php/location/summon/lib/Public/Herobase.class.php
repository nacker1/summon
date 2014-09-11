<?php
/**
 *@ 英雄基类
 **/
class Herobase extends Base{
	protected $heroBaseTable = 'zy_baseHero'; //英雄基类表
	protected $hid;		//英雄id
	protected $hInfo;	//指定英雄信息

	public function __construct( $hid='' ){
		$this->hid = $hid;
		parent::__construct();
		$this->_init();
	}

	private function _init(){
		$this->pre;
		if( C('test') || !$this->pre->exists('heroBase:check') ){
			$this->cdb;
			$ret = $this->cdb->find( $this->heroBaseTable );
			if( empty($ret) ){
				ret('no_baseHero_config');
			}
			foreach( $ret as $v ){
				$this->pre->hmset( 'heroBase:heroinfo:'.$v['Hero_Id'], $v );
			}
			$this->pre->hset( 'heroBase:check', 'check', 1, get3time() );
		}
		$this->hInfo = $this->pre->hgetall( 'heroBase:heroinfo:'.$this->hid );
	}

	public function getFire(){

	}
}
?>