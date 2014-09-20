<?php
/**
 *@ 用户任务类
 **/
 class User_Mission extends User_Base
 {
 	private $missionTable='zy_baseMissionConfig';			//任务配置表
 	private $userMissionTable='zy_uniqUserMission';			//用户任务进度表
 	private $dayMissionTag = 'missionTagEveryDay';			//日常任务标签
 	private $type;											//任务类型  1为任务，2为日常
 	private $errorInfo;										//错误信息

 	function __construct( $args )
 	{
 		# code...
 		parent::__construct( $args['uid'] );
 		$this->log->i('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
 		$this->type = $args['type'];	
 		$this->_init();
 	}

 	function _init(){
 		$this->pre;
 		if( C('test') || !$this->pre->hget( 'baseMissionConfig:'.$this->type.':check', 'checked' ) ){
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
 		}

 		if( $this->type == 1 ){ //系统任务  取数据库
 			$this->redis;
 			if( C('test') || !$this->redis->exists( 'roleinfo:'.$this->uid.':mission:11' ) ){
 				$this->db;
 				$ret = $this->db->find( $this->userMissionTable, 'showMission,missing,progress', array( 'uid'=>$this->uid,'status'=>0 ) ); 

 				$this->log->i( 'db_mission:'.json_encode($ret) );	
 				if( $ret && is_array( $ret ) ){
 					foreach( $ret as $v ){
 						$this->redis->del('roleinfo:'.$this->uid.':mission:'.$v['type']);
 						$this->redis->hmset( 'roleinfo:'.$this->uid.':mission:'.$v['type'],$v );
 					}
 				}else{
 					$taskClass = $this->pre->hgetall( 'baseMissionConfig:TaskClass_'.$this->type );
 					$this->redis->hdel( 'roleinfo:'.$this->uid.':mission:*' );
 					foreach( $taskClass as $k=>$v ){
 						$uMission[ $k ]['type'] = $k;			//任务类型
 						$set['showMission'] = $uMission[ $k ]['showMission'] = $v;
 						$set['missing'] = $uMission[ $k ]['missing'] = $v;
 						$uMission[ $k ]['time'] = time();
 						$set['progress'] = $uMission[ $k ]['progress'] = 0;
 						$uMission[ $k ]['uid'] = $this->uid;
 						if( $k == 21 ){
 							$hero  = new User_Hero();
 							$set['progress'] = $uMission[ $k ]['progress'] = $hero->getUserHeroNum();
 							$keys = $this->pre->keys( 'baseMissionConfig:1:121*' );
 							rsort($keys);
 							foreach( $keys as $val ){
 								$bMC = $this->pre->hmget( $val, array('Task_Id','Task_Time') );
 								if( $bMC['Task_Time'] <= $uMission[$k]['progress'] ){
 									$set['missing'] = $uMission[ $k ]['missing'] = $bMC['Post_Task'];
 								}
 							}
 						}
 						$this->redis->hmset( 'roleinfo:'.$this->uid.':mission:'.$k, $set );
 						$this->setThrowSQL($this->userMissionTable,$uMission[$k]);
 					}
 				}
 			}
 		}else{//日常任务
 			$overTime = get3time();
 			$this->cond = new Cond( $this->dayMissionTag,$this->uid,$overTime );
 			$uMission = $this->cond->getAll();
 			if( empty( $uMission ) ){ //初始化用户当日日常任务记录
 				$taskClass = $this->pre->hgetall( 'baseMissionConfig:TaskClass_'.$this->type );
 				foreach( $taskClass as $k=>$v ){
 					if( 61 == $k ){
 						$tasks = explode(',',$v);
 						foreach( $tasks as $val ){
 							$key = $k.':'.$val;
		 					$set['tid'] = (int)$val;		#'tid'		任务id
		 					$set['progress'] = 0;				#'progress' 进度
		 					$this->cond->set( $set,$key );
		 					unset($set);
 						}
 					}else{
	 					$set['tid'] = (int)$v; 	#'tid'		任务id
	 					$set['progress'] = 0; 		#'progress' 进度
	 					if( $k == 60 ){
	 						$set['progress'] = $this->isMonthCode();
	 					}
	 					$this->cond->set( $set,$k );
	 					unset($set);
	 				}
 				}
 			} 			
 		}
 	}
 /**
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
 			$dayMis = $this->cond->getAll();
 			foreach( $dayMis as $v ){
 				$set[0] = $v['tid'];
 				$set[1] = $v['progress'];
 				$uMission[] = implode( '|', $set );
 				unset($set);
 			}
 		}
 		$this->log->i( 'mList:'.json_encode($uMission) );
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
 			$this->log->i( json_encode($taskConfig).':userLevel->'.$this->getLevel() );
 			$this->errorInfo = ' min_level_'.(int)$taskConfig['Task_Level'];
 			return false;
 		}
 		if( 1 == $this->type ){ //系统任务领取处理
	 		$uMissProgress = $this->redis->hgetall( 'roleinfo:'.$this->getUid().':mission:'.$taskConfig['Task_Class'] );
	 		if( $uMissProgress['showMission'] != $taskId || $taskId == $uMissProgress['missing'] ){  //用户当前领取的任务实际未完成  返回
	 			$this->errorInfo = ' no_finished ';
	 			return false;
	 		}
	 		#设置用户当前showMission为下一任务id
	 		if( $this->setUserShowMission( $taskConfig['Task_Class'],$taskConfig['Post_Task'] ) ){
	 			$this->log->i( json_encode($taskConfig) );
	 			return $taskConfig['config']; 
	 		}else{
	 			$this->errorInfo = ' sys_fail_retry ';
	 			return false;
	 		}
	 	}elseif( 2== $this->type ){ //日常任务领取处理
	 		if( $taskConfig['Task_Class'] == 61 ){ //晚餐或午餐领取体力需要额外处理
	 			$mission = $this->cond->get( $taskConfig['Task_Class'].':'.$taskId );
		 		if( empty( $mission ) ){ //如果信息不存在说明键值已被删除，用户已领取过奖品
		 			$this->errorInfo = ' reward ';
		 			return false;
		 		}
		 			$time = date( 'Hi' );
		 			if( $time < $taskConfig['Task_Goal'] || $time > $taskConfig['Task_Time'] ){
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
 *	$type: 任务分类
 *	$taskId:	 任务id
 **/
	function setUserShowMission( $type,$taskId ){
		//$this->db->update( $this->userMissionTable, array('showMission'=>$taskId), array( 'uid'=>$this->uid, 'type'=>$type ) );
		$this->setThrowSQL( $this->userMissionTable, array('showMission'=>$taskId), array( 'uid'=>$this->uid, 'type'=>$type ) );
		return $this->redis->hset( 'roleinfo:'.$this->getUid().':mission:'.$type, 'showMission', $taskId );
	}
/**
 *@ setUserMissing 设置用户指定类型任务已完成任务的进度
 *@ param:
 *	$type: 任务分类
 **/
	function setUserMissing( $type ){
		$this->log->i('missionClass:'.$type.',this->type:'.$this->type);
		if( 1==$this->type ){ //处理系统任务
			$missing = $this->getUserMissingByClass($type);
			$set['progress'] = (int)$missing['progress'] + 1;
			$set['missing'] = (int)$missing['missing'];
			$key = empty( $missing['missing'] ) ? $missing['showMission'] : $missing['missing'] ;
			$baseMission = $this->pre->hmget( 'baseMissionConfig:'.$this->type.':'.$key,array( 'Task_Time','Post_Task','Task_Goal','Task_Level' ) );
			$this->log->i( 'baseMission:'.json_encode($baseMission) );
			if( $set['progress'] >= $baseMission['Task_Time'] ){
				$set['missing'] = $baseMission[ 'Post_Task' ];
				if( empty( $baseMission['Post_Task'] ) ){
					$set['status'] = 1;
				}
			}
			if( $type < 14 ){
				$set['progress'] = $baseMission[ 'Task_Goal' ];
			}
			
			#=====================  设置任务通知  ======================
			$notice[] = $missing['showMission'];
			$notice[] = $set['missing'];
			$notice[] = $set['progress'];
			$this->setMissionNotice( $this->type, $notice );
			#===========================================================
			$this->setThrowSQL( $this->userMissionTable, $set, array( 'uid'=>$this->uid, 'type'=>$type ) );
			if( empty( $baseMission[ 'Post_Task' ] ) ){
				return $this->redis->del( 'roleinfo:'.$this->getUid().':mission:'.$type );
			}else{
				return $this->redis->hmset( 'roleinfo:'.$this->getUid().':mission:'.$type, $set);
			}
		}elseif( 2==$this->type ){ //处理日常任务
			$dayMis = $this->cond->get($type);
			if( !empty( $dayMis ) ){
				$dayMis['progress'] += 1 ;
				$this->cond->set( $dayMis,$type );
				#=====================  设置任务通知  ======================
				$notice[] = $dayMis['tid'];
				$notice[] = $dayMis['progress'];
				$this->setMissionNotice( $this->type, $notice );
				#===========================================================
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
 }
?>