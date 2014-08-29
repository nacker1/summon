<?php
/**
 *@ 购买配置类
 **/
 class Buy extends Pbase{
 	private $buyConfig = array(
		'buyGold' => array( 'name'=>'zy_baseBuyGoldConfig','key'=>'BuyGold_Times'),
	); 												//购买标签配置

 	private $tag;											//购买类型
 	private $key;											//主key 对应表中的主key和redis中的主key
 	private $config;											//指定标签的配置信息

 	function __construct( $tag, $key ){
 		parent::__construct();
		$this->tag = $tag;
		$this->key = $key;
		$this->_init();		
 	}

 	private function _init(){
 		if( C('test') || ( !$this->pre->exists( 'baseBuyGoldConfig:'.$this->key ) && !$this->pre->hget( 'baseBuyGoldConfig:check', 'checked' ) ) ){
 			$this->cdb;
 			$ret = $this->cdb->find( $this->buyConfig[$this->tag]['name'] );
 			foreach( $ret as $v ){
 				$this->pre->hmset( 'baseBuyGoldConfig:'.$v[ $this->buyConfig[$this->tag]['key'] ], $v );
 			}
 			$this->pre->hset( 'baseBuyGoldConfig:check', 'checked', 1, get3time() );
 		}
 		$this->config = $this->pre->hgetall( 'baseBuyGoldConfig:'.$this->key );
 	}

 	public function getConfig(){
 		if( empty( $this->config ) ){ret('no_config',-1);}
 		return $this->config;
 	}
 }

?>