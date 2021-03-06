<?php
/**
 *@ 后台同步用户留存等数据
 **/
	define('ISLOG',true);
	require_once '/data/web/summon/inc/inc.php';
	$end = date('Y-m-d');
	$start = date('Y-m-d',strtotime('-1 days'));
	$sdb = Db_Mysql::init('stats');

//========================== 用户回访数据统计 ===========================
	function getSQL( $days ){
		$datetime = ( mktime(0,0,0)- 86400 );
		$sql = 'select count(1) nums,channel,date,sid from (select count(1) nums,zu.channel channel,FROM_UNIXTIME(nu.time,"%Y-%m-%d") date,zu.sid sid from ( select uid,UNIX_TIMESTAMP(time) time from zy_statsUserLoginLog where isNew=1 and time> "'.date('Y-m-d', $datetime-$days*86400 ).'" and time < "'.date('Y-m-d', $datetime-($days-1)*86400 ).'" ) nu,zy_statsUserLoginLog zu where zu.uid=nu.uid and zu.time>"'.date('Y-m-d',$datetime).'" and zu.time<"'.date('Y-m-d',mktime(0,0,0)).'" group by zu.sid,zu.channel,zu.uid) a group by channel,sid';
		$sdb = Db_Mysql::init('stats');
		$row = $sdb->query($sql);
		return $row;
	}

	$access[0] = getSQL( 0 );
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
				}
			}
		}
	}

	$sdb->close();
?>