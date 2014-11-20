<?php
/**
 *@ 用户任务类
 **/
 class User_Mission extends User_Base
 {
 	private static $dbCehck;								//DB模式下用户任务体系缓存
 	private $missionTable='zy_baseMissionConfig';			//任务配置表
 	private $dayMissionTag = 'missionTagEveryDay';			//日常任务标签
 	private $type;											//任务类型  1为任务，2为日常
 	private $class;											//任务小类型  对应任务表中的Class
 	private $errorInfo;										//错误信息

 	function __construct( $args )
 	{
 		# code...
 		parent::__construct( $args['uid'] );
 		$this->log->d('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
 		$this->type = $args['type'];	
 		$this->class = $args['class'];
 		$this->_init();
 	}

 	function _init(){
 		$this->pre;
 		if( C('test') || !$this->pre->exists( 'baseMissionConfig:'.$this->type.':check' ) ){
 			if( !isset( self::$dbCehck[$this->uid] ) || empty( self::$dbCehck[$this->uid] ) ){
	 			$this->pre->hdel( 'baseMissionConfig:'.$this->type.':*' );
	 			$this->cdb;
	 			$ret = $this->cdb->find( $this->missionTable,'*',array('Task_Type'=>$this->type) );
	 			foreach( $ret as $v ){
	 				empty( $v[ 'Item_Reward' ] ) ? '' : $temp['good'] = str_replace(array('#','*'),array(',',','),$v[ 'Item_Reward' ]);
					empty( $v[ 'PlayerExp_Reward' ] ) ? '' : $temp['exp'] = $v[ 'PlayerExp_Reward' ];
					empty( $v[ 'Money_Reward' ] ) ? '' : $temp['money'] = $v[ 'Money_Reward' ];
					empty( $v[ 'Diamond_Reward' ] ) ? '' : $temp['cooldou'] = $v[ 'Diamond_Reward' ];
					empty( $v[ 'PlayerAction_Reward' ] ) ? '' : $temp['life'] = $v[ 'PlayerAction_Reward' ];
					unset( $v['Item_Reward'], $v['PlayerExp_Reward'], $v['Money_Reward'], $v['Diamond_Reward'], $v[ 'PlayerAction_Reward' ] );
					$v['config'] = json_encode($temp);
	 				$this->pre->hmset( 'baseMissionConfig:'.$v['Task_Type'].':'.$v['Task_Id'], $v );
	 				if( substr($v['Task_Id'], -3) == 1 || $v['Task_Class'] == 61 ){ //初始化用户默认任务
	 					if( $v['Task_Class'] == 61 ){
	 						$initTaskClass[ $v['Task_Class'] ][] = $v['Task_Id'];
	 					}else{
	 						$initTaskClass[ $v['Task_Class'] ] = $v['Task_Id'];
	 					}
	 				}
	 				unset($temp);
	 			}
	 			if( isset( $initTaskClass['61'] ) ){ //午餐和晚餐任务特殊处理
					$initTaskClass['61'] = implode(',', $initTaskClass['61']);
				}
	 			$this->pre->del( 'baseMissionConfig:TaskClass_'.$this->type );
	 			$this->pre->hmset( 'baseMissionConfig:TaskClass_'.$this->type, $initTaskClass );
	 			$this->pre->hset( 'baseMissionConfig:'.$this->type.':check', 'checked',1,get3time() );
	 			self::$dbCehck[$this->uid] = 1;
	 		}
 		}

 		if( $this->type == 1 ){ //系统任务  取数据库
 			$this->redis;
 			if( C('test') || !$this->redis->exists( 'roleinfo:'.$this->uid.':mission:11' ) ){
 				$this->db;
 				$ret = $this->db->find( $this->userMissionTable, 'showMission,missing,progress,type', array( 'uid'=>$this->uid,'status'=>0 ) ); 

 				$this->log->d( 'db_mission:'.json_encode($ret) );	
 				if( $ret && is_array( $ret ) ){
 					foreach( $ret as $v ){
 						$this->redis->del('roleinfo:'.$this->uid.':mission:'.$v['type']);
 						$this->redis->hmset( 'roleinfo:'.$this->uid.':mission:'.$v['type'],$v );
 					}
 				}else{
 					$taskClass = $this->pre->hgetall( 'baseMissionConfig:TaskClass_'.$this->type );
 					$this->redis->hdel( 'roleinfo:'.$this->uid.':mission:*' );
 					foreach( $taskClass as $k=>$v ){
 						$set[$k]['type'] = $uMission[ $k ]['type'] = $k;			//任务类型
 						$set[$k]['showMission'] = $uMission[ $k ]['showMission'] = $v;
 						$set[$k]['missing'] = $uMission[ $k ]['missing'] = $v;
 						$uMission[ $k ]['time'] = time();
 						$set[$k]['progress'] = $uMission[ $k ]['progress'] = 0;
 						$uMission[ $k ]['uid'] = $this->uid;
 						if( $k == 21 ){
 							$hero  = new User_Hero($this->uid);
 							$set[$k]['progress'] = $uMission[ $k ]['progress'] = $hero->getUserHeroNum();
 							$keys = $this->pre->keys( 'baseMissionConfig:1:121*' );
 							rsort($keys);
 							foreach( $keys as $val ){
 								$bMC = $this->pre->hmget( $val, array('Task_Time','Post_Task') );
 								if( (int)$bMC['Task_Time'] <= $uMission[ $k ]['progress'] ){
 									$set[$k]['missing'] = $uMission[$k]['missing'] = (int)$bMC['Post_Task'];
 									break;
 								}
 							}
 						}
 						if( $k == 51 ){
 							$set[$k]['progress'] = $this->getLevel();
 						}
 						$this->redis->hmset( 'roleinfo:'.$this->uid.':mission:'.$k, $set[$k] );
 						$this->setThrowSQL($this->userMissionTable,$uMission[$k]);
 					}
 				}
 			}
 		}else{//日常任务
 			$overTime = get3time();
 			$this->cond = new Cond( $this->dayMissionTag,$this->uid,$overTime );
 		}
 	}

 	private function _initMissionData(){
		$uMission = $this->cond->getAll();
		if( empty( $uMission ) ){ //初始化用户当日日常任务记录
			$taskClass = $this->pre->hgetall( 'baseMissionConfig:TaskClass_'.$this->type );
			foreach( $taskClass as $k=>$v ){
				if( 61 == $k ){
					$tasks = explode(',',$v);
					foreach( $tasks as $val ){
						$key = $k.':'.$val;
	 					$set['tid'] = (int)$val;						#'tid'		任务id
	 					$set['progress'] = 0;							#'progress' 进度
	 					$this->cond->set( $set,$key );
	 					$uMission[] = $set;
 						unset($set);
					}
				}else{
					$set['tid'] = (int)$v; 								#'tid'		任务id
					$set['progress'] = 0; 								#'progress' 进度
					if( $k == 60 ){
						$set['progress'] = $this->isMonthCode();
					}
					$this->cond->set( $set,$k );
					$uMission[] = $set;
 					unset($set);
				}
			}
		} 
 		return $uMission;
 	}

 /**
  * 不加apc: 37.57 [#/sec]
  * 添加apc: 63.81 [#/sec]
  *@ getUserMission
  **/
 	private function getUserMission(){
 		if( 1 == $this->type ){
 			$keys = $this->redis->keys( 'roleinfo:'.$this->uid.':mission:*' );
 			foreach( $keys as $v ){
 				$mis = $this->redis->hgetall( $v );
 				$set[] = $mis['showMission'];
 				$set[] = $mis['missing'];
 				$set[] = $mis['progress'];
 				$uMission[ $mis['type'] ] = implode('|',$set);
 				unset($set);
 			}
 		}
 		if( 2 == $this->type ){
 			$dayMis = $this->_initMissionData();
 			foreach( $dayMis as $v ){
 				$set[0] = $v['tid'];
 				$set[1] = $v['progress'];
 				$uMission[] = implode( '|', $set );
 				unset($set);
 			}
 		}
 		$this->log->d( 'mList:'.json_encode($uMission) );
 		return $uMission;
 	}
/**
  *@ 获取当前用户的任务进度列表 
  **/
 	function getMissionList(){
 		$ret = array();
 		$uLevel = $this->getLevel();
 		$uMission = $this->getUserMission();
 		if( is_array( $uMission ) )
	 		foreach( $uMission as $v ){
	 			$mMinLevel = (int)$this->pre->hget( 'baseMissionConfig:'.$this->type.':'.$v['showMission'],'Task_Level' );
	 			#if( $uLevel >= $mMinLevel ){
	 			$ret[] = $v;
	 			#}
	 		}
 		return $ret;
 	}
/**
  *@ 任务奖励领取 
  **/
 	function getMissionGoods( $taskId ){
 		$taskConfig = $this->pre->hmget( 'baseMissionConfig:'.$this->type.':'.$taskId,array('Task_Level','config','Task_Class','Post_Task','Task_Time','Task_Goal') );
 		if( empty($taskConfig['config']) && empty( $taskConfig['Task_Level'] ) && empty( $taskConfig['Task_Class'] ) ){  //配置信息未取到 返回错误
 			$this->errorInfo = 'no_config';
 			return false;
 		}
 		if( (int)$taskConfig['Task_Level'] > $this->getLevel() ){ 	//用户当前等级小于任务要求的最低等级  返回
 			$this->log->d( 'taskConfig:'.json_encode($taskConfig).':userLevel->'.$this->getLevel() );
 			$this->errorInfo = ' min_level_'.(int)$taskConfig['Task_Level'];
 			return false;
 		}
 		if( 1 == $this->type ){ //系统任务领取处理
	 		$uMissProgress = $this->redis->hgetall( 'roleinfo:'.$this->getUid().':mission:'.$taskConfig['Task_Class'] );
	 		if( !empty( $uMissProgress['missing'] ) && $uMissProgress['showMission'] >= $uMissProgress['missing'] ){  //用户当前领取的任务实际未完成  返回
	 			$this->log->d( 'uMissProgress相应配置信息'.json_encode($uMissProgress) );
	 			$this->errorInfo = ' no_finished ';
	 			return false;
	 		}
	 		#设置用户当前showMission为下一任务id
	 		$this->setUserShowMission( $taskConfig['Task_Class'],$taskConfig['Post_Task'] );
 			$this->log->d( __LINE__.' taskConfig:'.json_encode($taskConfig) );
 			return $taskConfig['config']; 
	 	}elseif( 2== $this->type ){ //日常任务领取处理
	 		if( $taskConfig['Task_Class'] == 61 ){ //晚餐或午餐领取体力需要额外处理
	 			$mission = $this->cond->get( $taskConfig['Task_Class'].':'.$taskId );
		 		if( empty( $mission ) ){ //如果信息不存在说明键值已被删除，用户已领取过奖品
		 			$this->log->e( $taskId.'_相应任务的配置信息为空' );
		 			$this->errorInfo = ' reward ';
		 			return false;
		 		}
		 			$time = date( 'Hi' );
		 			if( $time < $taskConfig['Task_Goal'] || $time > $taskConfig['Task_Time'] ){
		 				$this->log->e( $time.'_'.json_encode($taskConfig) );
		 				$this->errorInfo = ' time_error ';
		 				return false;
		 			}
	 			$this->cond->del( $taskConfig['Task_Class'].':'.$taskId );
	 		}else{
	 			$mission = $this->cond->get( $taskConfig['Task_Class'] );
		 		if( empty( $mission ) ){ //如果信息不存在说明键值已被删除，用户已领取过奖品
		 			$this->errorInfo = ' reward ';
		 			return false;
		 		}
		 		if( $taskConfig['Task_Class'] == 60 ){
		 			$mission['progress'] = $this->isMonthCode();
		 		}
		 		$this->log->d( 'mission:'.json_encode($mission).', taskConfig:'.json_encode($taskConfig).', isMonthCode:'.$this->isMonthCode );
		 		if( $mission['progress'] < (int)$taskConfig['Task_Time'] ){
		 			$this->errorInfo = 'no_finished';
		 			return false;
		 		}
	 			$this->cond->del( $taskConfig['Task_Class'] );
	 		}
	 		return $taskConfig['config']; 
	 	}
 	}
/**
 *@ 任务奖励领取 
 **/
 	function getErrorInfo(){
 		$this->log->e( '#'.$this->getUid().'# 任务接口返回错误信息 =*'.$this->errorInfo.'*=' );
 		return $this->errorInfo;
 	}
/**
 *@ setUserShowMission 设置用户指定类型任务的当前领取任务id
 *@ param:
 *	$type:		 任务分类
 *	$taskId:	 任务id
 **/
	function setUserShowMission( $class,$nextTaskId ){
		$baseMission = $this->pre->hmget( 'baseMissionConfig:'.$this->type.':'.$nextTaskId,array( 'Post_Task' ) );
		$set['showMission'] = $nextTaskId;
		if( empty( $baseMission['Post_Task'] ) ){
			$set['status'] = 1;
			$this->redis->del( 'roleinfo:'.$this->getUid().':mission:'.$class );
		}else{
			$this->redis->hset( 'roleinfo:'.$this->getUid().':mission:'.$class, 'showMission', $nextTaskId );	
		}
		$this->setThrowSQL( $this->userMissionTable, $set, array( 'uid'=>$this->uid, 'type'=>$class ) );
		return true;
	}
/**
 *@ setUserMissing 设置用户指定类型任务已完成任务的进度
 **/
	function setUserMissing( $progress ){
		$this->log->d('missionClass:'.$this->class.',this->type:'.$this->type.', progress:'.$progress);
		if( 1==$this->type ){ //处理系统任务
			$missing = $this->getUserMissingByClass( $this->class );
			if( $missing['missing'] === '0' ){

				return;
			}
			$this->log->d( 'missing:'.json_encode( $missing ) );
			if( empty( $missing ) ) {
				$this->log->e( 'missing值为空,未取到用户当前正在进行的任务。missionClass:'.$this->class.',this->type:'.$this->type.', progress:'.$progress );
				return;
			}
			$set['progress'] = (int)$missing['progress'] + $progress;
			$set['missing'] = (int)$missing['missing'];
			$key = empty( $missing['missing'] ) ? $missing['showMission'] : $missing['missing'];
			$baseMission = $this->pre->hmget( 'baseMissionConfig:'.$this->type.':'.$key,array( 'Task_Time','Post_Task','Task_Goal','Task_Level' ) );
			$this->log->d( 'baseMission:'.json_encode($baseMission) );
			if( $set['progress'] >= $baseMission['Task_Time'] ){
				do{
					$key = $set['missing'] = $baseMission[ 'Post_Task' ];
					$nextMassion = $baseMission;
					if( !empty( $key ) )
						$baseMission = $this->pre->hmget( 'baseMissionConfig:'.$this->type.':'.$key,array( 'Task_Time','Post_Task','Task_Goal','Task_Level' ) );
				}while( $this->class > 13 && !empty( $key ) && $set['progress'] >= $baseMission['Task_Time'] );
			}
			if( $this->class < 14 ){
				$set['progress'] = $nextMassion[ 'Task_Goal' ];
			}
			
			#=====================  设置任务通知  ======================
			$set['showMission'] = $missing['showMission'];
			$this->setMissionNotice( $this->type,$this->class, $set );
			#===========================================================
			return $this->redis->hmset( 'roleinfo:'.$this->getUid().':mission:'.$this->class, $set);			
		}elseif( 2==$this->type ){ //处理日常任务
			$dayMis = $this->cond->get($this->class);
			if( !empty( $dayMis ) ){
				$dayMis['progress'] += $progress ;
				$this->cond->set( $dayMis,$this->class );
				$this->setMissionNotice( $this->type,$this->class, $dayMis );
			}			
			return true;
		}
	}
/**
 *@ getUserMissingByClass();  获取用户系统任务指定类型的进度信息
 **/
	function getUserMissingByClass( $type ){
		return $this->redis->hgetall( 'roleinfo:'.$this->getUid().':mission:'.$type);
	}

	function __destruct(){
	}
 }
?>