<?php
/**
 *@ 英雄基类
 **/
class Herobase extends Base{
	protected $heroBaseTable = 'zy_baseHero'; 					//英雄基类表
	protected $commentTable='zy_statsHeroComment';						//英雄信息评论表
	protected $hid;												//英雄id
	static protected $hInfo;									//指定英雄信息
	

	public function __construct( $hid='' ){
		if( empty( $hid ) )ret('no_hid');
		$this->hid = (int)$hid;
		parent::__construct();
		$this->_init();
	}

	private function _init(){
		$this->pre;
		if( empty( self::$hInfo ) ){
			if( C('test') || !$this->pre->exists('heroBase:check') ){
				$this->cdb;
				$ret = $this->cdb->find( $this->heroBaseTable,'Hero_Id,Hero_Hp,Hero_UpHp,Hero_Mp,Hero_UpMp,Hero_Att,Hero_UpAtt,Hero_Sor,Hero_UpSor,Hero_Def,Hero_UpDef,Hero_Res,Hero_UpRes,Hero_GetHp,Hero_UpGetHp,Hero_GetMp,Hero_UpGetMp,Hero_AttSpd,Hero_UpAttSpd,Hero_Mov,Hero_Pry',array( 'Hero_Id'=>array('<'=>20000) ) );
				if( empty($ret) ){
					ret('no_baseHero_config');
				}
				foreach( $ret as $v ){
					$this->pre->hmset( 'heroBase:heroinfo:'.$v['Hero_Id'], $v );
				}
				$this->pre->hset( 'heroBase:check', 'check', 1, get3time() );
			}
			self::$hInfo[$this->hid] = $this->pre->hgetall( 'heroBase:heroinfo:'.$this->hid );
		}
	}
/**
 *@ 计算英雄的战斗力
 *	英雄战斗力 = [（英雄物理攻击 + 英雄法术强度 + 英雄物理护甲 + 英雄魔法抗性）* 英雄攻击速度 +（英雄生命值 + 英雄生命回复 * 2 + 英雄法力值 + 英雄法力回复 * 2）] / 10 + 英雄拥有技能个数 * 100 *（1 + 英雄拥有技能的等级之和 / 10）
 **/
	public function getFire( $level=1,$color=1,$skill='{"1":"1"}' ){
		#dump(self::$hInfo);
		$att = self::$hInfo[$this->hid]['Hero_Att'] + self::$hInfo[$this->hid]['Hero_UpAtt'] * ( $level - 1 ) * $color; 								#英雄物理攻击
		$def = self::$hInfo[$this->hid]['Hero_Def'] + self::$hInfo[$this->hid]['Hero_UpDef'] * ( $level - 1 ) * $color;								#英雄物理护甲
		$sor = self::$hInfo[$this->hid]['Hero_Sor'] + self::$hInfo[$this->hid]['Hero_UpSor'] * ( $level - 1 ) * $color;								#英雄法术强度
		$res = self::$hInfo[$this->hid]['Hero_Res'] + self::$hInfo[$this->hid]['Hero_UpRes'] * ( $level - 1 ) * $color;								#英雄法术抗性
		$speed = self::$hInfo[$this->hid]['Hero_AttSpd'] + self::$hInfo[$this->hid]['Hero_UpAttSpd'] * ( $level - 1 ) * $color;						#英雄的攻击速度
		$hp = self::$hInfo[$this->hid]['Hero_Hp'] + self::$hInfo[$this->hid]['Hero_UpHp'] * ( $level - 1 ) * $color;									#英雄的生命值
		$mp = self::$hInfo[$this->hid]['Hero_Mp'] + self::$hInfo[$this->hid]['Hero_UpMp'] * ( $level - 1 ) * $color;									#英雄的魔法值
		$gethp = self::$hInfo[$this->hid]['Hero_GetHp'] + self::$hInfo[$this->hid]['Hero_UpGetHp'] * ( $level - 1 ) * $color;							#英雄的生命回复
		$getmp = self::$hInfo[$this->hid]['Hero_GetMp'] + self::$hInfo[$this->hid]['Hero_UpGetMp'] * ( $level - 1 ) * $color;							#英雄的魔法回复

		$skill = json_decode( $skill, true );
		$sTolLevel = 0;						#英雄技能等级总和
		if( is_array( $skill ) )
			foreach( $skill as $v ){
				$sTolLevel += $v;
			}

		return floor( ( ($att+$def+$sor+$res)*$speed + ( $hp + $gethp * 2 + $mp + $getmp * 2 ) )/10 ) + $color*100*( 1+number_format($sTolLevel/10,3) );
	}
/**
 *@ 评价英雄
 *@param:
 * 	$hid: 		英雄id
 *	$uinfo:		发表评论的用户信息
 *	$comment:	评论内容
 **/
	function commentHero( $uinfo, $comment ){
		$set['hid'] = $this->hid;
		$set['uinfo'] = $uinfo;
		$set['comment'] = $comment;
		$set['agree'] = 1;
		$set['time'] = time();
		$this->throwSQL( $this->commentTable, $set, '', '', 'stats');
	}
/**
 *@ 英雄点赞
 *@param:
 * $cid :  评论id
 **/
	function laudHero( $cid ){
		$this->sdb;
		$sql = 'update '.$this->commentTable.' set `agree`=agree+1 where `id`='.$cid;
		return $this->sdb->query( $sql );
	}
/**
 *@ 获取英雄评论信息
 *@param:
 * $type :  排序方式   1 按赞排序， 2 按时间排序
 * $page :  打开页码
 **/
	function getComment( $order, $page ){
		$pagesize = 30;
		$this->sdb;
		if( $order == 1 ){
			$sql = ' select * from `'.$this->commentTable.'` where hid='.$this->hid.' order by `agree` desc limit '.$pagesize*($page-1).','.$pagesize;
		}elseif( $order == 2 ){
			$sql = ' select * from `'.$this->commentTable.'` where hid='.$this->hid.' order by `time` desc limit '.$pagesize*($page-1).','.$pagesize;
		}
		return $this->sdb->query( $sql );
	}
}
?>