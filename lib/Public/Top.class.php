<?php
/**
 *@ 排行榜通用类
 **/
class Top extends Base{
	private $type;						//排行榜类型 1为金币排行榜  2为等级排行  3为钻石排行榜
	private $redisTag='topList';		//排行榜在redis中的标签
	private $topPre;					//排行榜redis连接源

	function __construct( $type, $uid ){
		parent::__construct( $uid );
		$this->type = $type;
		$this->topPre = new Cond( $this->redisTag.':'.$this->type );
	}

	function getTopList(){
		$ret = $this->topPre->get();
		if( empty( $ret ) ){
			$this->redis;
			switch( $this->type ){
				case '1':
					$sql = 'select id uid,nickname,image,money num,level from zy_uniqRole order by money desc limit 10';
					break;
				case '2':
					$sql = 'select id uid,nickname,image,jewel num,level from zy_uniqRole order by level desc limit 10';
					break;
				case '3':
					$sql = 'select id uid,nickname,image,jewel num,level from zy_uniqRole order by jewel desc limit 10';
					break;
			}
			$ret = $this->redis->query( $sql );
			foreach( $ret as $v ){
				$temp[] = $v['uid'];
				$temp[] = $v['image'];
				$temp[] = $v['level'];
				$temp[] = $v['num'];
				$temp[] = $v['nickname'];
				$ret[] = $temp;
			}
			$this->setTopList( $ret );
		}
		return $ret;
	}

	function setTopList($top){/*
		for( $i=0;$i<10;$i++ ){
			$top[$i] = array( 381440+$i, rand( 1,10 ), 80-2*$i, 10000 - 100*$i ,'name_'.$i);
		}*/
		return $this->topPre->set($top);
	}
}
?>