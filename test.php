<?php
	function king( $total, $num ){
		$temp = range(1,$total);
		$count = count($temp);
		$i=0;
		while( $count>1 ){
			$i++;
			$head = array_shift($temp);
			if( $i % $num != 0 ){
				array_push($temp, $head);
			}else{
				echo "编号为：".$head."  的人被杀\n";
			}
			$count = count($temp);
		}
		return $temp[0];
	}
	#echo king( 30, 8 );


	$host = isset( $_SERVER['HTTP_REFERER'] ) ? parse_url($_SERVER['HTTP_REFERER']) : '';
	$hostname = isset($host['host']) ? $host['host'] : '';
	var_dump($hostname);
	var_dump($_SERVER);
/**
 *@ 测试脚本
 **/
	//echo iconv('UTF-8','GBK','鐢ㄦ埛娌℃湁缁戝畾.');exit;
	require dirname(__FILE__).'/inc/inc.php';

/*	$curl = new Curl('http://api.yunva.com:8199/api');
	$data['method'] = 'videoManage';
	$data['uId'] = '111111';
	$data['appId'] = '500003';
	$data['msgId'] = '1';
	$data['seq'] = 'videoManage';
	$data['state'] = '0';
	$data['appKey'] = md5($data['appId'].$data['msgId'].'i23kdoo09jjl<h');
	dump($curl->post($data));
	exit;*/
/*
	$user = new User_User(381440);
	$hero = new User_Hero( $user->getUid() );
	$heroList = $hero->getUserHeroList();
	foreach( $heroList as $v ){
		unset($v['id']);
		unset($v['uid']);
		unset($v['exp']);
		$heroinfo .= '#'.json_encode($v);
	}
	$ret['name'] = $user->getUserName();
	$ret['img'] = $user->getImage();
	$ret['rank'] = 1000;
	$ret['fire'] = '1000';
	$ret[ 'hero' ] = trim($heroinfo,'#');
	$pre->hmset( 'pvpTeamInfo:'.$user->getUid(), $ret);
	$pre->hmset( 'pvpTopList:1000',array( 'name'=>'测试号','img'=>$user->getImage(), 'uid'=>$user->getUid(), 'level'=>$user->getLevel() ) );
	for( $i=1;$i<1000;$i++ ){
		$server['name'] = $ret['name'].'_'.$i;
		$server['img'] = $i%3;
		$server['rank'] = $i;
		$server['fire'] += $i;
		$pre->hmset( 'pvpTeamInfo:'.($user->getUid() + $i ) , $server);
		$pre->hmset( 'pvpTopList:'.$i,array( 'name'=>$server['name'],'img'=>$server['img'], 'uid'=>$i, 'level'=>$i+1 ) );
	} */
?>