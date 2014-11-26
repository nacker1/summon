<?php
/**
 *@ 战争学院逻辑
 **/
	class User_War extends User_Base{
		private $war_table='zy_baseWarExp';					#战争学院的配置表
		private $type;										#修炼方式
		private $level;										#玩家等级
		private $warInfo;									#修炼的配置信息

		function __construct( $uid, $type, $level ){
			parent::__construct($uid);
			$this->type 	= $type;
			$this->level 	= $level;
			$this->_init();
			$this->cond = new Cond( $war_table, $uid );
		}
		
		/**
		 *@ 初始化战争学院数据
		 **/
		private function _init(){
			$this->pre;
			if( C( 'test' ) || !$this->pre->exists( 'war_base_check:'.$this->type ) ){
				$this->cdb;
				$ret = $this->cdb->find( $this->war_table, '*', array( 'War_Type'=>$this->type ) );
				foreach( $ret as $v ){
					$this->pre->hmset( 'war_base:'.$this->type.':'.$v['Player_Level'], $v );
				}
				$this->pre->set( 'war_base_check:'.$this->type, 1, get3time() );
			}
			$this->warInfo = $this->pre->hgetall( 'war_base:'.$this->type.':'.$this->level );
		}
		/**
		 *@ 开始修炼
		 **/
		function begin(){
			$set['level'] = $this->level;
			$set['time'] = time();
			return $this->cond->set( $set, $this->type );
		}

		/**
		 *@ 领取修炼结果
		 **/
		function end(){
			return $this->cond->get( $this->type );
		}

		/**
		 *@ 获取修炼需要的花费
		 **/
		function getMoney(){
			if( $this->warInfo['Currency_Type'] == 1 ){
				$ret['type'] = 'money';
			}else{
				$ret['type'] = 'jewel';
			}
			$ret['nums'] = $this->warInfo['War_Cost'];
			return $ret;
		}
	}
?>