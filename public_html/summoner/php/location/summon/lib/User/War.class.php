<?php
/**
 *@ 战争学院逻辑
 **/
	class User_War extends User_Base{
		private $war_table='zy_baseWarExp';					#战争学院的配置表
		private $type;										#修炼方式   1金币修炼   2为低级钻石修炼  3为高级钻石修炼
		private $level;										#玩家等级
		private $warInfo;									#修炼的配置信息

		function __construct( $uid, $type='', $level='' ){
			parent::__construct($uid);
			$this->type 	= $type;
			if( !in_array( $this->type, array( 1,2,3 ) ) ){ret('YMD',-1);}
			$this->level 	= $level;
			$this->_init();
			$this->cond = new Cond( $this->war_table, $uid );
		}
		
		/**
		 *@ 初始化战争学院数据
		 **/
		private function _init(){
			if( !empty( $this->type ) && !empty( $this->level ) ){   #开始修炼时运行
				$this->pre;
				if( C( 'test' ) || !$this->pre->exists( 'war_base_check:'.$this->type ) ){
					$this->cdb;
					$ret = $this->cdb->find( $this->war_table, '*', array( 'War_Type'=>$this->type ) );
					foreach( $ret as $v ){
						$this->pre->hmset( 'war_base:'.$this->type.':'.$v['Player_Level'], $v );
					}
					$this->pre->set( 'war_base_check:'.$this->type, 1, get3time() );
				}
				#开始修改时运行
				$this->warInfo = $this->pre->hgetall( 'war_base:'.$this->type.':'.$this->level );
			}
		}
		/**
		 *@ 开始修炼
		 **/
		function begin(){
			$set['type'] = $this->type;								#修炼方式
			$set['exp'] = $this->warInfo['War_Exp'];				#修炼得到的经验
			$set['time'] = time();									#修炼时间
			$set['costTime'] = $this->warInfo['War_Time'] * 60;		#修炼完成需要的时间（秒）
			return $this->cond->set( $set, $this->type );
		}

		/**
		 *@ 领取修炼结果
		 **/
		function over(){
			$war = $this->cond->get( $this->type );
			if( !$war ){ 
				$this->error = ' 请先修炼 ';
				return false;
			}
			if( ( time() - $war['time'] ) < $war['costTime'] ){
				$this->error = ' 正在修炼中。。。 ';
				return false;
			}
			$this->end();
			return $war['exp'];
		}
/**
 *@ 检测当前修炼类型是否还在修炼或未领取奖励   未修炼 返回false    正在修炼返回 true
 **/
		function checkWaring(){
			for( $i=1;$i<4;$i++ ){
				$this->log->d( $this->cond->get( $this->type ) );
				if( $this->cond->get( $this->type ) ){
					return true;
				}
			}
			return false;
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
	/**
	 *@ 获取当前用户的所有修炼的剩余时间
	 *return:
	 *		array( 1=>0,2=>-1,3=>600 )  第一种修炼可以领取奖励，第二种修炼可以直接修炼，第三种修炼剩余600秒
	 **/
		function getStatus(){
			$ret = array(1=>-1,2=>-1,3=>-1);
			$wars = $this->cond->getAll();
			foreach( $wars as $v ){
				$times = $v[ 'costTime' ] - time() + $v[ 'time' ];  #剩余时间（秒）
				$ret[ $v['type'] ] = $times>0 ? $times : 0;
			}
			return $ret;
		}
/**
 *@ 结束修炼
 **/		
		function end(){
			return $this->cond->del( $this->type );
		}
/**
 *@ 返回修炼错误信息
 **/		
		function getError(){
			return $this->error;
		}
	}
?>