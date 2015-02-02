<?php
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
			$ret = $this->cdb->find( $this->notice_table, 'title,start,end,content' , array( 'end'=>array( '>'=>time() ) ) );
			if( !empty( $ret ) )
				$this->pre->set( $this->notice_table, json_encode( $ret ), get3time() );
			$this->pre->set( $this->notice_table.'_check',1,get3time() );
		}
	}

	function getNoticeList(){
		$nList = json_decode( $this->pre->get( $this->notice_table ) , true);
		if( empty( $nList ) ) return '';
		$ret = array();
		foreach( $nList as $v ){
			$ret[$i]['title'] = $v['title'];
			$ret[$i]['content'][0]['titleSub'] = '活动时间：'.date('Y-m-d H:i:s',$v['start']).' - '..date('Y-m-d H:i:s',$v['end']);
			$ret[$i]['content'][0]['contentSub'] = $v['content'];
		}
		return $ret;
	}
}
?>