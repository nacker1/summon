<?php
/**
 *@ 用户反馈
 **/
 class User_Feedback extends User_Base{
 	private $table = 'zy_statsFeedback';	//用户反馈表
 	private $type;				//用户反馈类型  1 默认反馈类型

 	function __construct( $type=1 ){
 		parent::__construct();
 		$this->log->i('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
 		$this->type = $type;
 		if( empty( $this->type ) ){
 			$this->type = 1;
 		}
 	}

 	function putContents( $con ){
 		$insert['uid'] = $this->uid;
 		$insert['name'] = $this->getUserName();
 		$insert['con'] = addslashes($con);
 		$insert['type'] = $this->type;
 		$insert['sid'] = $this->getServerId();
 		$insert['time'] = date('Y-m-d H:i:s');
 		$this->setThrowSQL( $this->table,$insert,'',1,'stats' );
 		//$this->sdb->insert( $this->table,$insert );
 	}
 }
?>