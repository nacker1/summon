<?php
/**
 *@ 用户信息修改
 **/
 $user = new User_User();
 
 $type = isset( $input['t'] ) ? $input['t'] : '1';

 switch ($type) {
 	case '1': #
 		$image = isset($input['img']) ? $input['img'] : '' ;
		$name = isset($input['name']) ? $input['name'] : '' ;

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
						if( false === $user->reduceCooldou( 100 ) ){
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
 	default:
 		# code...
 		break;
 }

 
 ret( ' ^.^ ',-1 );

?>