<?php
/**
 *@ 竞技场奖励配置表
 **/
class Reward extends Base{
	static public $reward_config;									#奖励配置信息

	private $reward_table='zy_baseArenaReward';						#配置表名
	private $level;													#竞技场排名
	private $tag;													#竞技场排名标签  有排名等级确定


	function __construct( $level ){
		parent::__construct();
		$this->level = $level;
		$this->_init();
	}	

	private function _init(){
		$this->pre;
		$this->_getTag();
		# 取奖励配置信息
		if( !isset( self::$reward_config[$this->tag] ) || empty( self::$reward_config[$this->tag] ) ){
			if( C('test') || !$this->pre->exists( $reward_table.':check' ) ){
				$this->cdb;
				$ret = $this->cdb->find( $reward_table, '*' );
				foreach( $ret as $v ){
					$temp['jewel'] = $v['Arena_Diamond'];
					$temp['money'] = $v['Arena_Gold'];
					$temp['mArena'] = $v['Arena_FighterMoney'];
					$temp['good'] = str_replace( '#',',',$v['Arena_ItemReward1'] ).'#'.str_replace( '#',',',$v['Arena_ItemReward2'] );
					$this->pre->set( $reward_table.':'.$v['Arena_RankMin'], json_encode( $temp ) );
					unset( $temp );
				}
				$this->pre->set( $reward_table.':check', 1, get3time() );
			}
			self::$reward_config[$this->tag] = $this->pre->get( $reward_table.':'.$this->tag );
		}
	}
/**
 *@ 设置排名标签
 **/
	private function _getTag(){
		switch( 1 ){
			case $level < 11:
				$this->tag = $this->level;break;
			case $level < 21:
				$this->tag = 11; break;
			case $level < 31:
				$this->tag = 21; break;
			case $level < 41:
				$this->tag = 31; break;
			case $level < 51:
				$this->tag = 41; break;
			case $level < 71:
				$this->tag = 51; break;
			case $level < 101:
				$this->tag = 71; break;
			case $level < 201:
				$this->tag = 101; break;
			case $level < 301:
				$this->tag = 201; break;
			case $level < 401:
				$this->tag = 301; break;
			case $level < 501:
				$this->tag = 401; break;
			case $level < 701:
				$this->tag = 501; break;
			case $level < 1001:
				$this->tag = 701; break;
			case $level < 2001:
				$this->tag = 1001; break;
			case $level < 3001:
				$this->tag = 2001; break;
			case $level < 4001:
				$this->tag = 3001; break;
			case $level < 5001:
				$this->tag = 4001; break;
			case $level < 7001:
				$this->tag = 5001; break;
			case $level < 10001:
				$this->tag = 7001; break;
			default:#10000名以后
				$this->tag = 10001; break;
			
		}
	}

	function getRewardConfig(){
		$this->log->d( 'rewardConfig:'.json_encode(self::$reward_config[$this->tag]) );
		return self::$reward_config[$this->tag];
	}
}
?>