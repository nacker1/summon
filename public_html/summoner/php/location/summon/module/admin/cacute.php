<?php
/**
 *@ 战场结算
 **/
	$str = '{"bossid":0,"buff":[71001],"cmd":9001,"currnk":0,"diamond":0,"goods":[{}],"heroexp":100,"heros":[10002,10004,10003,10005,10001],"hrank":0,"isboss":0,"money":1000,"pass":1,"playerexp":10,"roundid":3,"stageid":3,"stagetype":2,"tasktype":69,"uid":381440}';
	$input = json_decode($str,true);
	if( count( $input )<5 ){
		$log->e( '* 战斗请求数据格式不对.'.json_encode($input) );
		ret( ' error_data ',-1 );
	}
	if( $input['errno'] != 0 ){
		$log->e( '* errno != 0 '.json_encode($input) );
		ret( 'error',-1 );
	}
	$uid = $input['uid'];
	$user = new User_User( $uid,-1 );

	$pass = $input['pass'];
	$sweepNum = isset($input['sweepNum']) ? $input['sweepNum'] : 1;  //结算次数

# ----------------------------------------------------------每天竞技场打斗限制-------------------------------------------------------------------------------------
	if( 64==$input['tasktype'] ){
		$log->i('竞技场战斗结算');					
		$custLimit = new User_Limit( 'doArenaTimesDay' ); 
		$user->setMissionId(2,64);
		$pvpTop = $input['currnk']; #当前排名
		if( $user->getUserRecord( 'maxPvpTop' ) < 1 || $pvpTop < $user->getUserRecord( 'maxPvpTop' ) ){
			$user->setUserRecord('maxPvpTop', $pvpTop );
			if( $input['diamond'] > 0 ){
				$add['cooldou'] = $input['diamond'];		#添加钻石   当前排名高于历史最高排名奖励钻石
			}
		}
		$custLimit->addLimitTimes( 1 );
		unset( $custLimit );
	}
# ----------------------------------------------------------每天竞技场打斗限制-------------------------------------------------------------------------------------	
	if( $pass ){//通关成功需要处理的事务
# ----------------------------------------------------------每天精英关卡与炼狱关卡通关次数限制-------------------------------------------------------------------------------------
		if( in_array( $input['tasktype'], array( 12,13 ) ) ){					
			$custLimit = new User_Limit( 'customsTimesDay' ); 
			if( $custLimit->getLastTimes( $input['stageid'] ) < 1 ){
				ret('今日次数已用完',-1);
			}
			$custLimit->addLimitTimes( 1,$input['stageid'] );
			unset( $custLimit );
		}
# ----------------------------------------------------------每天精英关卡与炼狱关卡通关次数限制-------------------------------------------------------------------------------------
		if( 4 == $input['stagetype'] ){//扫荡处理 英雄经验转化成药水
			if( empty($input['isboss']) ){
				$log->e( '* 关卡不是大关卡，无法扫荡。');
				ret( 'YMD'.__LINE__,-1 );
			}
			//--------------------------扫荡扣除钻石------------------------------
			$limit = new User_Limit( 'freeSweepTimesDay' );
			$cooldou = $limit->getOneTimeCooldou();
			if( $cooldou > 0 && $user->reduceCooldou( $limit->getExpend() ) === false ){
				ret(' no_jewel ',-1);
			}
			$limit->addLimitTimes(1);
			//--------------------------------------------------------
			$tolHeroExp = $input['heroexp'] * 5 * $sweepNum;
			$expBase = new Goodbase( 63002 );
			$config = $expBase->getGoodConfig();
			$exp1 = $config['Hero_Exp'];

			$expBase = new Goodbase( 63003 );
			$config = $expBase->getGoodConfig();
			$exp2 = $config['Hero_Exp'];

			$nums['63002'] = floor( $tolHeroExp / $exp1 );
			$nums['63003'] = floor( ($tolHeroExp%$exp1)/$exp2 );
			foreach( $nums as $k=>$v ){
				if( $v<1 )continue;
				$good = new User_Goods( $user->getUid(), $k );
				$good->addGoods( $v );
				$ext[$k] = $v;
			}
			$input['ext'] = $ext;
			unset($nums,$exp1,$exp2,$expBase,$config,$tolHeroExp,$ext);
		}else{//给英雄添加经验
			$heros = $input['heros'];
			if( is_array( $heros ) ){
				foreach( $heros as $v ){
					$hero[$v] = new User_Hero( $uid, $v );
					$hero[$v]->addHeroExp( $input['heroexp'] );
				}
				$input['heros'] = $hero[$v]->getLastUpdField();
			}

			//-==============处理用户通关进度 PVE==============
			if( in_array($input['stagetype'],array(1) ) && $input['stageid'] > 0 && $input['passlevel'] > 0 ){
				$progress = new User_Progress( $input['stageid'] );
				$progress->setUserProgress( $input['passlevel'] );
			}
			
		}

		if( isset( $input['buff'] ) && is_numeric( $input['buff'] ) ){ //活动添加buff  buff应对buff表中的buffid
			$user->addRoleBuff( $input['buff'] );
		}
		
		switch( $input['tasktype'] ){  //通关扣除体力
			case '11': 	//普通关卡
				$add['life'] = -6*$sweepNum;
				break;
			case '12':	//精英关卡
				$add['life'] = -12*$sweepNum;
				$user->setMissionId(2,15);
				break;
			case '13':	//炼狱关卡
				$add['life'] = -24*$sweepNum;
				$user->setMissionId(2,16);
				break;
			case '69':	//呆小红
				$add['life'] = -6;
				$user->setMissionId(2,69);
				break;
			case '70':	//呆小蓝
				$add['life'] = -6;
				$user->setMissionId(2,70);
				break;
			case '71':	//无尽之地
				$add['life'] = -6;
				$user->setMissionId(2,71);
				break;
			case '68':	//英雄炼狱
				$add['life'] = -6;
				$user->setMissionId(2,68);
				break;
			case '66':	//黄金矿山
				$add['life'] = -6;
				$user->setMissionId(2,66);
				break;
		}
		if( $input['playerexp'] > 0 ){
			$add['exp'] = $input['playerexp'] * $sweepNum;		#添加召唤师经验
		}
		if( $input['money'] > 0 ){
			$add['money'] = $input['money'] * $sweepNum;		#添加金币
		}

		if( isset($input['tasktype']) && $input['tasktype'] < 14 && $input['tasktype'] > 10 ){ // pve逻辑处理
			$proxy = new Proxy( 1, 'User_Mission', 'getUserMissingByClass' );
			$miss = $proxy->exec( $input['tasktype'] );
			if( $miss['progress']<$input['stageid'] ){//当前通关关卡比之前通关关卡id大，设置系统任务与用户通关进度
				$user->setMissionId( 1, $input['tasktype'] );
			}

			#======================= 神密商店处理逻辑 ==============================
			if( $user->getVlevel() > 9 ){
				$input['vshop'] = 1;
			}else{
				$uLevel = $user->getLevel();
				$input['vshop'] = 0;
				if( $uLevel > 29 ){
					if( $uLevel > 60 ){
						$rate = 30;
					}else{
						$rate = 30 - ( 60-$uLevel ) * 0.5;
					}
					if( isLucky( $rate/100 ) ){
						$input['vshop']  = 1;
					}
				}
			}
			#======================= 神密商店处理逻辑 ==============================	
		}
		if( is_array($input['goods']) && count( $input['goods'] ) > 0 ){
			foreach( $input['goods'] as $val ){
				if( is_array( $val ) && count( $val )>0 )
					foreach( $val as $k=>$v ){
						$good = new User_Goods( $user->getUid(), $k );
						$good->addGoods( $v );
					}
			}
			gettype($good) == 'object' && $input['getGoods'] = $good->getLastUpdGoods();
		}
		$log->i( json_encode($add) );
	}else{
		$input['goods'] = '';
		/*switch( $input['tasktype'] ){  //通关失败扣除体力
			case '':		//其它活动
			case '11':	//普通关卡
			case '12':	//精英关卡
			case '13':	//炼狱关卡
				$add['life'] = -1;
				break;
		}*/

		if(64 != $input['tasktype'] )
			$add['life'] = -1;
	}

	$input['getList'] = $user->sendGoodsFromConfig( $add ); 	//所有条件通过后统一发放物品

#============================每日刷副本日常任务=================================
	if( in_array( $input['tasktype'], array( 11,12,13 ) ) ){
		//====  日常任务处理  ===========
		$user->setMissionId( 2, 14 ); //每日所有副本任务
	}
#============================每日刷副本日常任务=================================
	ret( $input );
