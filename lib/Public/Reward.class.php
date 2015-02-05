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
		$this->_getTag();
		# 取奖励配置信息
		if( !isset( self::$reward_config[$this->tag] ) || empty( self::$reward_config[$this->tag] ) ){
			$this->pre;
			if( C('test') || !$this->pre->exists( $this->reward_table.':check' ) ){
				$this->cdb;$this->preMaster;
				$ret = $this->cdb->find( $this->reward_table );
				if( !empty( $ret ) ){
					foreach( $ret as $v ){
						$temp['jewel'] = $v['Arena_Diamond'];
						$temp['money'] = $v['Arena_Gold'];
						$temp['mArena'] = $v['Arena_FighterMoney'];
						$temp['good'] = str_replace( '#',',',$v['Arena_ItemReward1'] ).'#'.str_replace( '#',',',$v['Arena_ItemReward2'] );
						$temp['good'] = trim( $temp['good'],'#' );
						$this->preMaster->set( $this->reward_table.':'.$v['Arena_RankMin'], json_encode( $temp ) );
						unset( $temp );
					}
					$this->preMaster->set( $this->reward_table.':check', 1, get3time() );
				}else{
					$this->log->f('pvpReward no config');
					ret( '竞技场配置表为空', -1 );
				}
			}
			self::$reward_config[$this->tag] = $this->pre->get( $this->reward_table.':'.$this->tag );
		}
	}
/**
 *@ 设置排名标签
 **/
	private function _getTag(){
		switch( 1 ){
			case $this->level < 11:
				$this->tag = $this->level;break;
			case $this->level < 21:
				$this->tag = 11; break;
			case $this->level < 31:
				$this->tag = 21; break;
			case $this->level < 41:
				$this->tag = 31; break;
			case $this->level < 51:
				$this->tag = 41; break;
			case $this->level < 71:
				$this->tag = 51; break;
			case $this->level < 101:
				$this->tag = 71; break;
			case $this->level < 201:
				$this->tag = 101; break;
			case $this->level < 301:
				$this->tag = 201; break;
			case $this->level < 401:
				$this->tag = 301; break;
			case $this->level < 501:
				$this->tag = 401; break;
			case $this->level < 701:
				$this->tag = 501; break;
			case $this->level < 1001:
				$this->tag = 701; break;
			case $this->level < 2001:
				$this->tag = 1001; break;
			case $this->level < 3001:
				$this->tag = 2001; break;
			case $this->level < 4001:
				$this->tag = 3001; break;
			case $this->level < 5001:
				$this->tag = 4001; break;
			case $this->level < 7001:
				$this->tag = 5001; break;
			case $this->level < 10001:
				$this->tag = 7001; break;
			default:#10000名以后
				$this->tag = 10001; break;
			
		}
		$this->log->d( $this->tag );
	}

	function getRewardConfig(){
		$this->log->d( 'rewardConfig:'.json_encode(self::$reward_config[$this->tag]) );
		return self::$reward_config[$this->tag];
	}
}
?>