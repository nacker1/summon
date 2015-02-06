<?php
/**
 *@ 排行榜通用类
 **/
class Top extends Base{
	private $type;						//排行榜类型 1为金币排行榜  2为等级排行  3为钻石排行榜 4为竞技场排行榜
	private $redisTag='topList';		//排行榜在redis中的标签
	private $topPre;					//排行榜redis连接源

	function __construct( $type, $uid='' ){
		parent::__construct( $uid );
		$this->type = $type;
		$this->topPre = new Cond( $this->redisTag.':'.$this->type, '', 3600 );
	}

	function getTopList(){
		$ret = $this->topPre->get();
		if( empty( $ret ) ){
			unset($ret);
			$this->db;
			switch( $this->type ){
				case '1':
					$sql = 'select userid uid,nickname,image,jewel num,level from zy_uniqRole order by jewel desc limit 10';
					break;
				case '2':
					$sql = 'select userid uid,nickname,image,jewel num,level from zy_uniqRole order by level desc limit 10';
					break;
				case '3':
					$sql = 'select userid uid,nickname,image,money num,level from zy_uniqRole order by money desc limit 10';
					break;
			}
			$sqlRet = $this->db->query( $sql );
			foreach( $sqlRet as $v ){
				$temp[] = $v['uid'];
				$temp[] = $v['image'];
				$temp[] = $v['level'];
				$temp[] = $v['num'];
				$temp[] = $v['nickname'];
				$ret[] = $temp;
				unset($temp);
			}
			$this->setTopList( $ret );
		}
		return $ret;
	}

	function setTopList($top){
		return $this->topPre->set( $top );
	}
/**
 *@ 发送PVP竞技场排名奖励  每日晚上9点发放
 **/
	function sendPvpReward(){
		$this->preMaster;$this->pre=$this->preMaster;
		$mail = new User_Mail( ADMIN_UID, 1 );
		for( $i=1; $i<15001; $i++ ){
			$key = 'pvpTopList:'.$i;
			$uid = $this->preMaster->hget( $key, 'uid' );
			if( empty( $uid ) ) continue;
			if( substr( $uid, 0, 2 ) == '38' || (int)$uid >= 1000000 ){
				$con = '你在竞技场的精彩表现有目共睹。截至今天21:00，你的竞技场排名为'.$i.'名。角斗士联盟授予你以下奖励：';
				$reward = new Reward( $i );
				$mail->sendMail( $con, 2, $uid, '竞技场每日排名奖励', $reward->getRewardConfig(), '竞技场军需官  阿杰' );
			}
		}
		return true;
	}
}
?>