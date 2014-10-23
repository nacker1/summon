<?php
/**
 *@ 公共数据操作类
 **/
class Submit extends Base{
	private $comment='zy_statsHeroComment';						//英雄信息评论表
/**
 *@ 评价英雄
 *@param:
 * 	$hid: 		英雄id
 *	$uinfo:		发表评论的用户信息
 *	$comment:	评论内容
 **/
	function commentHero( $hid, $uinfo, $comment ){
		$set['hid'] = $hid;
		$set['uinfo'] = $uinfo;
		$set['comment'] = $comment;
		$set['agree'] = 1;
		$set['time'] = time();
		$this->throwSQL( $this->comment, $set, '', '', 'stats');
	}
/**
 *@ 英雄点赞
 * $cid :  评论id
 **/
	function laudHero( $cid ){
		$set['agree'] = 'agree+1';
		$this->throwSQL( $this->comment, $set, array('id'=>$cid), '', 'stats');
	}
}
?>