<?php
/**
 *@ 文字过滤
 **/
class Filter extends Pbase {
	private $filterTable = 'zy_baseFilter';		//敏感词库
	private $char;					//需要过滤的文字

	public function __construct( $char ){
		parent::__construct();
		$this->char = preg_replace('/\s/','',$char);
	}

	public function isOk(){
		$this->cdb;
		$ret = $this->cdb->findOne( $this->filterTable,'id',array( 'word'=>$this->char ) );
		return $ret ? false : true ;
	}

	public function filterSql(){
		return addslashes($this->char);
	}
}
?>