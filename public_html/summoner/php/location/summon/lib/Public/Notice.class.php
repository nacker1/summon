<?php
echo time();exit;
/**
 *@ Notice 公告类
 **/
class Notice extends Base{
	private $notice_table = 'zy_baseNotice' ;				#公告表

	function __construct(){
		parent::__construct();
		$this->_init();
	}

	function _init(){
		$this->pre;
		if( C('test') || !$this->pre->exists( $this->notice_table.'_check' ) ){
			$this->log->d( '~~~~~~~~~~~~~~~~~~~~~ DB_SELECT ~~~~~~~~~~~~~~~~~~~~~~' );
			$this->cdb;
			$ret = $this->cdb->find( $this->notice_table, array( 'end'=>array( '>'=>time() ) ) );
			var_dump( $ret );
			if( !empty( $ret ) )
				$this->pre->set( $this->notice_table, json_encode( $ret ), get3time() );
			$this->pre->set( $this->notice_table.'_check',1,get3time() );
		}
	}

	function getNoticeList(){
		return $this->pre->get( $this->notice_table );
	}
}
?>