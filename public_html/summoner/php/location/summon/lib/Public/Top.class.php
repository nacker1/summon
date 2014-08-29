<?php
/**
 *@ 排行榜通用类
 **/
class Top extends Base{
	private $type;				//排行榜类型 1为金币排行榜  2为等级排行
	private $redisTag='topList';		//排行榜在redis中的标签
	private $topPre;			//排行榜redis连接源

	function __construct( $type ){
		parent::__construct();
		$this->type = $type;
		$this->topPre = new Cond( $this->redisTag.':'.$this->type );
	}

	function getTopList(){
		return $this->topPre->get();
	}

	function setTopList(){
		for( $i=0;$i<10;$i++ ){
			$top[$i] = array( 381440+$i, rand( 1,10 ), 80-2*$i, 10000 - 100*$i ,'name_'.$i);
		}
		return $this->topPre->set($top);
	}
}
?>