<?php
/**
 *@ 用户信息修改
 **/
 $user = new User_User();
 
 $type = isset( $input['t'] ) ? $input['t'] : '1';

 switch ($type) {
 	case '1': #
 		$image = isset($input['img']) ? $input['img'] : '' ;
		$name = isset($input['name']) ? trim($input['name']) : '' ;

		if( !empty( $image ) ){
		 	if( $image != $user->getImage() ){
				$user->setUserImage( $image );
			}
			ret( array('image'=>$image) );
		}

		if( !empty( $name ) ){
		 	$filter = new Filter( $name );
		 	if( $filter->isOk() ){
		 		if( $name != $user->getUserName() ){
				 	if( $user->getUserName() != $user->getRid().$user->getServerId() ){
						if( false === $user->reduceCooldou( EDIT_USERNAME_COOLDOU ) ){
							$log->e('* 用户#'.$user->getUid().'#钻石不足，无法修改名称'.$user->getUserName().'->'.$name);
							ret( '钻石不足',-1 );
						}
					}
					if( $user->getInfo( $name ) ){
						ret( '名称已存在', -1 );
					}
					$user->setUserName($name);
				}
				ret( $user->getUserLastUpdInfo() );
			}else{
				ret(' 您的名字被系统定性为敏感词，请重新输入！ ',-1);
			}
		}
 		break;
 	case '2': #记录用户新手引导进度
 		$index = $input['index'];
 		$gid = $input['gid'];
 		ret( $user->setUserGuide( $index, $gid ) );
 		break;
 	case '3': #用户禁言功能
	 	$gag = new Cond( 'user_limit', $user->getUid(), GAG_TIME );
	 	$gag->set( 1,'gag' );
	 	$ret['v'] = $gag->get('gag');
	 	$ret['t'] = $gag->getTimes('gag');
	 	ret($ret);
 	case '4': #用户战争学院修炼功能
 		$tag = ' 战争学院修炼 ';
 		if( $user->getLevel() < WAR_LOW_LEVEL ){ret( '召唤师等级不足', -1 );}
 		$type = $input[ 'wt' ];					#修炼方式  1普通   2中级  3为高级
	 	$war = new User_War( $user->getUid(), $type, $user->getLevel() );
	 	$money = $war->getMoney();
	 	if( $user->getTypeInfo( $money['type'] ) < $money['nums'] ){
	 		ret( '对应货币不足', -1 );
	 	}
	 	if( $war->checkWaring() ){
	 		ret( '正在修炼中。。。',-1 );
	 	}
	 	$add[$money['type']] = -$money['nums'];
	 	$ret = $user->sendGoodsFromConfig( $add );
	 	$ret['war'][ $type ] = $war->begin();
	 	ret( $ret );
 	case '5':   #领取战争学院奖励
 		$tag = ' 战争学院奖励领取 ';
 		if( $user->getLevel() < WAR_LOW_LEVEL ){ret( '召唤师等级不足', -1 );}
 		$type = $input[ 'wt' ];					#修炼方式  1普通   2中级  3为高级
	 	$war = new User_War( $user->getUid(), $type );
	 	$exp = $war->over();
	 	if( !$exp ){
	 		ret( $war->getError(), -1 );
	 	}
	 	$add[ 'exp' ] = $exp;
	 	ret( $user->sendGoodsFromConfig( $add ) );
 	case '6':#查询战争学院各种修炼模式的状态
 		$tag = ' 战争学院修炼 ';
 		if( $user->getLevel() < WAR_LOW_LEVEL ){ret( '召唤师等级不足', -1 );}
	 	$war = new User_War( $user->getUid() );
	 	ret( $war->getStatus() );

 	case '7':#战争学院修炼敲醒功能
 		$tag = '战争学院修炼敲醒';
 		if( $user->getLevel() < WAR_LOW_LEVEL ){ret( '召唤师等级不足', -1 );}
	 	
	 	$limit = new User_Limit(  $user->getUid(),'strikeTimesDay' );
	 	if( $limit->getLastTimes() < 1 ){
		 	ret( ' 敲醒次数已用完 ',-1);
		}
	 	$war = new User_War( $user->getUid() );
	 	if( $war->isWake() ){
	 		ret( '修炼大师清醒中...',-1 );
	 	}
	 	$ret['war']['action'] = $war->wakeUp();
	 	$ret['war']['list'] = $war->getStatus();
	 	$limit->addLimitTimes(1);
	 	ret( $ret );
     case '8': #用户禁言状态查询

         $gag = new Cond( 'user_limit', $user->getUid(), 600 );
         $ret['v'] = $gag->get('gag');
         $ret['t'] = $gag->getTimes('gag');
         ret($ret);
         break;
     case '9': #用户解禁
         $gag=new Redis();
         $gag = new Cond( 'user_limit', $user->getUid(), 600 );
         $gag->del( 'gag' );
         $ret['v'] = $gag->get('gag');
         $ret['t'] = $gag->getTimes('gag');
         ret($ret);
         break;
 	default:
 		# code...
 		break;
 }

 
 ret( ' ^.^ ',-1 );

?>