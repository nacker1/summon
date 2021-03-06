<?php
/**
 *@ 战场结算
 **/
	$str = '{"bossid":0,"buff":[72001],"cmd":9001,"currnk":0,"diamond":0,"goods":[{}],"heroexp":100,"heros":[],"hrank":0,"isboss":0,"money":1000,"pass":1,"playerexp":10,"roundid":3,"roundid":960002,"stagetype":2,"tasktype":70,"uid":14}';
	
	if( count( $input )<5 ){
		$log->e( '* 战斗请求数据格式不对.'.json_encode($input) );
		$input = json_decode($str,true);
		#ret( ' error_data ',-1 );
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
		$tag = '挑战竞技场';
		$log->i('竞技场战斗结算');					
		$custLimit = new User_Limit( $user->getUid(), 'doArenaTimesDay' ); 
		$user->setMissionId(2,64);
		$pvpTop = $input['currnk']; 							#当前排名
		$input['hrank'] = $user->getUserRecord( 'maxPvpTop' );	#历史最高排名
		if( $input['hrank'] < 1 || $pvpTop < $input['hrank'] ){
			$user->setUserRecord('maxPvpTop', $pvpTop );
			if( $pvpTop < 5001 ){ #发钻石
				if( $input['hrank'] > 5000 || $input['hrank'] < 1 ){
					$add['cooldou'] = $input['diamond'] = ceil((5001-$pvpTop)*0.5*(1+(1-$pvpTop/5000)));
				}elseif( $pvpTop < $input['hrank'] ){
					$add['cooldou'] = $input['diamond'] = ceil(($input['hrank']-$pvpTop)*0.5*(1+(1-$pvpTop/5000)));
				}
			}
		}
		$custLimit->addLimitTimes( 1 );
		unset( $custLimit );
	}
# ----------------------------------------------------------每天竞技场打斗限制-------------------------------------------------------------------------------------	
	if( $pass ){//通关成功需要处理的事务
		/*switch( $input['tasktype'] ){  //通关体力预判断
			case '66':	//黄金矿山
			case '68':	//英雄炼狱
			case '69':	//呆小红
			case '70':	//呆小蓝
			case '71':	//无尽之地
				$reLife = $*$sweepNum;
				break;
			case '11': 	//普通关卡
				$reLife = 6*$sweepNum;
				break;
			case '12':	//精英关卡
				$reLife = 12*$sweepNum;
				break;
			case '13':	//炼狱关卡
				$reLife = 24*$sweepNum;
				break;
		}*/
		$reLife = 0;
		if( $input['playerexp'] > 0 ){
			$reLife = $input['playerexp'] * $sweepNum;
		}
		if( $user->getLife() < $reLife ){
			ret( '体力不足', -1 );
		}
		$add['life'] = -$reLife;
# ----------------------------------------------------------每天精英关卡与炼狱关卡通关次数限制-------------------------------------------------------------------------------------
		if( in_array( $input['tasktype'], array( 12,13 ) ) ){					
			$custLimit = new User_Limit( $user->getUid(), 'customsTimesDay' ); 
			if( $custLimit->getLastTimes( $input['roundid'] ) < $sweepNum ){
				ret('今日次数已用完',-1);
			}
			$custLimit->addLimitTimes( $sweepNum,$input['roundid'] );
			unset( $custLimit );
		}
# ----------------------------------------------------------每天精英关卡与炼狱关卡通关次数限制-------------------------------------------------------------------------------------
		if( 4 == $input['stagetype'] ){//扫荡处理 英雄经验转化成药水
			$tag = 'pve扫荡';
			if( empty($input['isboss']) ){
				$log->e( '* 关卡不是大关卡，无法扫荡。');
				ret( 'YMD'.__LINE__,-1 );
			}
			//--------------------------扫荡扣除钻石------------------------------
			$limit = new User_Limit( $user->getUid(), 'freeSweepTimesDay' );
			$cooldou = $limit->getExpend();
			$freeTime = $limit->getLastFreeTimes();
			$log->e( '* freeTime:'.$freeTime );
			if( $freeTime < $sweepNum ){
				if( $cooldou > 0 && $user->getCooldou() < ( $cooldou*( $sweepNum-$freeTime ) ) ){
					ret(' no_jewel ',-1);
				}else{
					$add['jewel'] = -( $cooldou*( $sweepNum-$freeTime ) );	
				}
			}

			$limit->addLimitTimes( $sweepNum );
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
				$ext[$k] = $v;
				$temp_add_good[] = $k.','.$v;
			}
			$input['ext'] = $ext;
			#$input['playerexp'] = 0;
			unset($nums,$exp1,$exp2,$expBase,$config,$tolHeroExp,$ext);
		}else{//给英雄添加经验
			$heros = $input['heros'];
			if( is_array( $heros ) && count( $heros > 0 ) ){
				foreach( $heros as $v ){
					$hero = new User_Hero( $uid, $v );
					$hero->addHeroExp( $input['heroexp'] );
				}
				if( !empty( $hero ) )
					$updHero = $hero->getLastUpdField();
				if( !empty( $updHero ) )
					$input['getList']['hero'] = $updHero;
			}

			//-==============处理用户通关进度 PVE 包括普通本 精英本 练狱本==============
			if( in_array($input['stagetype'],array(1) ) && $input['roundid'] > 0 && $input['passlevel'] > 0 ){
				$progress = new User_Progress( $input['roundid'] );
				$progress->setUserProgress( $input['passlevel'] );
			}
		}

		switch( $input['tasktype'] ){  //通关扣除体力
			case '11': 	//普通关卡
				$tag = 'pev普通关卡';
				$user->setMissionId( 2, 14,$sweepNum ); //每日所有副本任务
				break;
			case '12':	//精英关卡
				$tag = 'pev困难关卡';
				$user->setMissionId(2,15,$sweepNum);
				break;
			case '13':	//炼狱关卡
				$tag = 'pev炼狱关卡';
				$user->setMissionId(2,16,$sweepNum);
				break;
			case '69':	//呆小红
				$tag = '活动呆小红';
				$user->setMissionId(2,69);
				$actLimit = new User_Limit( $user->getUid(), 'minRedDay' );
				$actLimit->addLimitTimes();
				break;
			case '70':	//呆小蓝
				$tag = '活动呆小蓝';
				$user->setMissionId(2,70);
				$actLimit = new User_Limit( $user->getUid(),'minBlueDay' );
				$actLimit->addLimitTimes();
				break;
			case '71':	//无尽之地  赏金之路
				$tag = '活动赏金之路';
				$add['mAction'] = $input['endlessmoney'];  #赏金之路钱
				$user->setMissionId(2,71);
				$actLimit = new User_Limit( $user->getUid(), 'endLessFieldDay', $input['roundnum'] );
				$actLimit->addLimitTimes();
				break;
			case '68':	//英雄炼狱
				$user->setMissionId(2,68);
				if( $input['stageid'] == 960003 ){ #钢铁巢穴
					$tag = '活动钢铁巢穴';
					$actLimit = new User_Limit( $user->getUid(), 'steelNestDay' );
					$actLimit->addLimitTimes();
				}elseif( $input['stageid'] == 960004 ){#飞龙宝藏
					$tag = '活动飞龙宝藏';
					$actLimit = new User_Limit( $user->getUid(), 'hiryuTreasuresDay' );
					$actLimit->addLimitTimes();
				}elseif( $input['stageid'] == 960005 ){#猎杀巨龙
					$tag = '活动猎杀巨龙';
					$actLimit = new User_Limit( $user->getUid(), 'killDragonDay' );
					$actLimit->addLimitTimes();
				}
				break;
			case '66':	//黄金矿山
				$tag = '活动黄金矿山';
				$user->setMissionId(2,66);
				$actLimit = new User_Limit( $user->getUid(), 'goldMineDay' );
				$actLimit->addLimitTimes();
				break;
		}
		
		if( isset( $input['buff'][0] ) && is_numeric( $input['buff'][0] ) ){ //活动添加buff  buff应对buff表中的buffid
			$input['getList']['buff'] = $user->addRoleBuff( $input['buff'][0] );
			unset( $input['buff'] );
		}

		
		if( $input['playerexp'] > 0 ){
			$add['exp'] = $input['playerexp'] * $sweepNum;		#添加召唤师经验
		}
		if( $input['money'] > 0 ){
			$add['money'] = $input['money'] * $sweepNum;		#添加金币
		}

		if( isset($input['tasktype']) && $input['tasktype'] < 14 && $input['tasktype'] > 10 ){ // pve逻辑处理
			$proxy = new Proxy( array('type'=>1,'uid'=>$user->getUid()), 'User_Mission', 'getUserMissingByClass' );
			$miss = $proxy->exec( $input['tasktype'] );
			if( $miss['progress'] <= $input['roundid'] ){//当前通关关卡比之前通关关卡id大，设置系统任务与用户通关进度
				$user->setMissionId( 1, $input['tasktype'] );
			}

			#======================= 神密商店处理逻辑    广宇确认只要是vip即可显示vip商店 ==============================
			if( $user->getVlevel() > 3 ){
				$input['getList']['vshop'] = 1;
			}else{
				$uLevel = $user->getLevel();
				$input['getList']['vshop'] = -1;

				if( $uLevel > 8 ){
					if( $uLevel > 60 ){
						$rate = 30;
					}else{
						$rate = 30 - ( 60-$uLevel ) * 0.5;
					}
					#$rate = 100;
					if( isLucky( $rate/100 ) ){
						$shop = new User_Shop( $uid, 2 );
						$times = $shop->getShopLastTime();
						$log->d( 'vshopLastTime:'.$times );
						$input['getList']['vshop']  = 3590;
						if( $times > 0 ){
							$input['getList']['vshop'] = $times;
						}else{
							$shop->getTypeItems();
						}
					}
				}
			}

			#======================= 神密商店处理逻辑 ==============================	
		}
		if( is_array($input['goods']) && count( $input['goods'] ) > 0 ){
			foreach( $input['goods'] as $val ){
				if( is_array( $val ) && count( $val )>0 )
					foreach( $val as $k=>$v ){
						$temp_add_good[] = $k.','.$v;
					}
			}
		}
	}else{
		$input['goods'] = '';
		if(64 != $input['tasktype'] )
			$add['life'] = -1;
	}
#============================添加友情点=================================
	if( isset($input['merc']) && is_numeric( $input['merc'] )){
		$friend = new User_Friend( $user->getUid() );
		$add['mFriend'] = 5;
		if( $friend->isFriend( $input['merc'] ) )
			$add['mFriend'] = 10;
		$merc = new User_Merc( $user->getUid() );
		$merc->addHadUid( $input['merc'] );
	}

#============================每日刷副本日常任务=================================
	$log->d( 'addInfo:'.json_encode($add) );
	isset($temp_add_good) && is_array($temp_add_good) && $add['good'] = implode('#',$temp_add_good);
	$updInfo = $user->sendGoodsFromConfig( $add ); 	//所有条件通过后统一发放物品
	if( !isset( $input['getList'] ) ){
		$input['getList'] = array();
	}
	if( isset( $updInfo ) && is_array( $updInfo ) )
		$input['getList'] = array_merge( $input['getList'], $updInfo );
	/*$mis = $user->getMissionNotice();
	if( !empty( $mis ) )
		$input['getList']['mis'] = $mis;*/

	ret( $input );
