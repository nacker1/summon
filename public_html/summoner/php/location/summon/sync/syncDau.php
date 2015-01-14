<?php
/**
 *@ 后台同步用户留存等数据
 **/
	define('ISLOG',true);
	require_once dirname(__FILE__).'/inc/inc.php';
	$end = date('Y-m-d');
	$start = date('Y-m-d',strtotime('-1 days'));
	$db = Db_Mysql::init();
	$sdb = Db_Mysql::init('stats');
	$log = new Logger('syncStatsLog','/data/web/ddz/logs/sync/');
    	
	$pre= Redis_Redis::initRedis();
	$datetime = ( mktime(0,0,0)- 86400 );

//========================== 用户回访数据统计 ===========================
	function getSQL( $days ){
		$datetime = ( mktime(0,0,0)- 86400 );
		echo $sql = 'select count(1) nums,channel,date from (select count(1) nums,zu.channel channel,FROM_UNIXTIME(nu.time,"%Y-%m-%d") date,zu.sid sid from ( select uid,time from zy_statsUserLoginLog where isNew=1 and time> '.( $datetime-$days*86400 ).' and time < '.( $datetime-($days-1)*86400 ).' ) nu,zy_statsUserLoginLog zu where zu.uid=nu.uid and zu.isNew=0 and zu.time>'.$datetime.' and zu.time<'.mktime(0,0,0).' group by zu.sid,zu.channel,zu.uid) a group by channel';
		exit;
		$sdb = Db_Mysql::init('stats');
		$row = $sdb->query($sql);
		return $row;
	}

	$access[1] = getSQL( 1 );
	$access[3] = getSQL( 3 );
	$access[4] = getSQL( 4 );
	$access[5] = getSQL( 5 );
	$access[6] = getSQL( 6 );
	$access[7] = getSQL( 7 );
	$access[15] = getSQL( 15 );
	$access[30] = getSQL( 30 );

	foreach( $access as $key=>$val ){
		if( is_array( $val ) ){
			foreach( $val as $v ){
				unset($insert);
				if( $v['nums'] > 0 && !empty( $v['date'] ) ){
					$insert['nums'] = $v['nums'];
					$insert['date'] = $v['date'];
					$insert['days'] = $key;
					$insert['channel'] = empty($v['channel'])?1:$v['channel'];
					$insert['sid'] = $v['sid'];
					$sdb->insert('zy_statsUserBack',$insert);
					$log->i($sdb->getLastSql());
				}
			}
		}
	}

//========================== 牌桌数据统计 ===========================
	$dayTag = date( 'd',strtotime('-1 days') ) % 2;
	//$dayTag = date( 'd' ) % 2;
	$roomConf = $db->find( 'Room' );
	$tol_room_nums_sql = 'SELECT count(1) count,vid,channel FROM zy_roomLog_'.$dayTag.' group by vid,channel';  //各渠道房间总次数
	$tol_user_nums_sql = 'select count(1) count,a.vid vid,a.channel channel from (SELECT count(1) count,vid,uid,channel FROM zy_roomLog_'.$dayTag.' group by vid,uid) a group by a.vid,a.channel'; //房间总人数 
	function getTimeNum( $time=1 ){
		$dayTag = date( 'd',strtotime('-1 days') ) % 2;
		$user_nums_sql = 'select count(1) c,a.vid vid,a.channel channel from (SELECT count(1) count,vid,channel FROM zy_roomLog_'.$dayTag.' group by vid,uid) a where a.count>='.$time.' group by a.vid,a.channel'; //每天至少time局人数
		$sdb = Db_Mysql::init('stats');
		$row = $sdb->query($user_nums_sql);
		foreach( $row as $v ){
			$ret[ $v['vid'] ][ $v['channel'] ] = $v[ 'c' ];
		}
		return $ret;
	}

	//维尼到此一游；
	$tolUser = $sdb->query( $tol_user_nums_sql );
	foreach( $tolUser as $v ) {
		$retTolUser[ $v['vid'] ][ $v['channel'] ] = $v['count'];
	}
	foreach( $roomConf as $v ){
		$retRoomConf[$v['id']] = $v['tip'];
	}
	$one = getTimeNum(1);
	$three = getTimeNum(3);
	$five = getTimeNum(5);
	$ten = getTimeNum(10);
	$tolRoom = $sdb->query( $tol_room_nums_sql );
	foreach( $tolRoom as $v ){
		unset($insert);
		$insert['vid'] = $v['vid'];
		$insert['Nums'] = $retTolUser[ $v['vid'] ][ $v['channel'] ];
		$insert['Inn'] = floor($v['count']/3);
		$insert['Reve'] = $v['count'] * $retRoomConf[ $v['vid'] ];
		$insert['one'] = $one[ $v['vid'] ][ $v['channel'] ];
		$insert['three'] = $three[ $v['vid'] ][ $v['channel'] ];
		$insert['five'] = $five[ $v['vid'] ][ $v['channel'] ];
		$insert['ten'] = $ten[ $v['vid'] ][ $v['channel'] ];
		$insert['time'] = date( 'Y-m-d', strtotime(' -1 days ') );
		$insert['channel'] = $v['channel'];
		$sdb->insert('zy_roomNums',$insert);
	}
	$flushDb = ' delete from zy_roomLog_'.$dayTag.' where id>0 ';  //处理完成后清空昨天表数据
	$sdb->query( $flushDb );

	#= 各渠道DAU数据入库 ===========================================================================================================
	$cList = $db->find( 'zy_channelList', 'cId' );
	foreach( $cList as $v ){
		$com = 'php /data/web/ddz/2/dau.php -e '.$end.' -s '.$start.' -c '.$v['cId'].'  >> /data/web/ddz/2/uploads/dau/'.$start.'_'.$v['cId'].'.log';
		exec($com);
	}
	#============================================================================================================

	$db->close();
	$sdb->close();
?>