<?
session_start();
include("../../sql_func.php");
include "m_lib/m_lib.php";
include("../../libs/sources.php");
include("../../libs/nations.php");
include "../../libs/users.php";
include "../../libs/msg.php";
require_once "../../Comet/Realplexor.php";
sql_connect();
$ObjSessionResult = mob_restore_session($PHPSESSID, $PHPUSERID);
$valid_user_name=$ObjSessionResult["valid_user_name"];

$delete_value=-10;
$bonus_for_rating_diff=10;

if($valid_user_name){
	$MyObj = Mob_UserInfo(0, "tiny", $valid_user_name);
	$my_id=$MyObj["id"];
	if($method=="putMark"){ 
		//проверка параметров
		if((($base!="music")&&($base!="fotos")&&($base!="users_fotos")&&($base!="clips")&&($base!="books")) || !check_integer($t_key)){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Oops! Не корректный параметр атрибута."));
			echo json_encode($method_status);
			exit;
		}
		$q="select f.bonus, uf.access_rules from forum_users f left outer join user_info uf on f.id=uf.id where f.name='".$valid_user_name."'";
		$r=mysql_query($q);
		$bonus=mysql_result($r,0,"bonus");
		$user_access_rules=access_rules_Decode(mysql_result($r,0,"access_rules"));
		$id=$MyObj["id"];
		$user_avatar=$MyObj["foto"];
		$user_gender=$MyObj["gender"];

		$q="select * from vote_base where base='".$base."' and id=".$id." and t_key=".$t_key;
		$r=mysql_query($q);
		is_db_error($r);
		//если оценка уже учтена
		if(mysql_num_rows($r)){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Ваша оценка уже учтена"));
			echo json_encode($method_status);
			exit;
		}
		else{
			$q="select votes, rating from ".$base." where num=".$t_key;
			$r=mysql_query($q);
			$vr=mysql_fetch_array($r);
			$votes=$vr['votes']+1;
			if($value=="plus"){
			   $rating=$vr['rating']+1;
			   $mark="+";
			   $add_value=1;
			}
			else if($value=="minus") {
			   $rating=$vr['rating']-1;
			   $mark="-";
			   $add_value=-1;
			}
			else if($value=="superplus") {
			   //если оценка суперплюс, то проверяем наличие бонусов
			   if($bonus>=3){
				   $q="update forum_users set bonus=bonus-3 where id=".$id;
				   $r=mysql_query($q);
				   echo mysql_error();
				   is_db_error($r);
				   $rating=$vr['rating']+5;
				   $mark="++";
				   $add_value=5;
			  }
			  else{
				$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Не достаточно бонусов для оценки 5+"));
				echo json_encode($method_status);
				exit;
			  }
			}
			else {
				$rating=-1;
				$mark="-";
				exit;
			}

			//если это ресурсы пользователей
			if(($base=="fotos")||($base=="users_fotos")||($base=="clips")||($base=="music")||($base=="books")){
			   $q="select * from ".$base." where num='".addslashes($t_key)."'";
			   $r=mysql_query($q);
			   if(mysql_num_rows($r)>0){
					$source=mysql_fetch_array($r);
					$q="select * from forum_users where name='".$source['owner']."'";
					$r=mysql_query($q);
					$owner=mysql_fetch_array($r);
					$owner_row=$owner;
					//если в черном списке
					$q="select * from blacklist where num1=".$owner['id']." and num2=".$id;
					$r3=mysql_query($q);
					if(mysql_num_rows($r3)>0){
						$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Вы находитесь в черном списке этого пользователя"));
						echo json_encode($method_status);
						exit;
					}
					//устанавливаем флаг, если ресурс нужно удалить
					
					if($rating<$delete_value){
						total_source_deleting($t_key,$base);
						$service_msg="Здравствуйте, рейтинг одного из ваших ресурсов опустился ниже ".$delete_value.". Согласно нашим правилам, такие ресурсы удаляются системой автоматически, так как в большинстве случаев не соответствуют правилам нашего сайта ( http://www.shax-dag.ru/club_law.php ). Вы можете оспорить это решение обратившись с письмом к администрации: http://www.shax-dag.ru/feedback.php";
						send_service_msg($owner['id'],"",$service_msg,"");
						$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Спасибо за оценку! Ресурс удален за низкий рейтинг"));
						echo json_encode($method_status);
						exit;
					}
					if($mark=="-" && $source['owner']!=$valid_user_name && $source['mod']==0 && $rating<=-2 && ($base=="fotos" || $base=="music" || $base=="clips" || $base=="books")){ //если ресурс не модерирован, то удаляем при рейтинге -2
						total_source_deleting($t_key,$base);
						$service_msg="Здравствуйте, один из ваших ресурсов, ожидающих модерацию, был оценен отрицательно другими пользователями. Согласно нашим правилам, такие ресурсы удаляются системой автоматически, так как в большинстве случаев не соответствуют правилам нашего сайта ( http://www.shax-dag.ru/club_law.php ). Вы можете оспорить это решение обратившись с письмом к администрации: http://www.shax-dag.ru/feedback.php";
						send_service_msg($owner['id'],"",$service_msg,"");
						$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Спасибо за оценку! Ресурс удален за низки рейтинг"));
						echo json_encode($method_status);
						exit;
					}
					else
					if($mark=="+" && $source['owner']!=$valid_user_name && $source['mod']==0 && $rating>=1 && ($base=="fotos" || $base=="music" || $base=="clips" || $base=="books")){ //если ресурс не модерирован, то принимаем 
						$q="update ".$base." set `mod`=1 where num='".addslashes($t_key)."'";
						$r3=mysql_query($q);
						is_db_error($r3);
					}
					
					
					//обновляем рейтинг пользователя
					$q="select 1 from user_info where id=".$owner['id'];
					$r=mysql_query($q);
					if(($base=="music")||($base=="clips")||($base=="books"))
						$coef_rating=5;
					else
						$coef_rating=1;
					if(mysql_num_rows($r)>0){
						$q="update LOW_PRIORITY user_info set rating=rating+".$coef_rating*$add_value." where id=".$owner['id'];
					}
					else
						$q="insert into user_info(id, rating) values(".$owner['id'].", ".$coef_rating*$add_value.")";
					$r=mysql_query($q);
					
			   }
			   else
					$owner['owner']="";
			   $q="insert into vote_base(base,id,t_key, owner, mark,time) values('".$base."',".$id.",".$t_key.",'".$owner['name']."', '".$mark."',".time().")";  
			   $r=mysql_query($q);
			   is_db_error($r);
			}
			else{
				$q="insert into vote_base(base,id,t_key,mark,time) values('".$base."',".$id.",".$t_key.",'".$mark."',".time().")";
				$r=mysql_query($q);
				is_db_error($r);
			}
			repair_table(mysql_error(), "vote_base");
			//обновляем ресурс
			//начисляем бонусы за хорошую музыку
			if(($base=="music")&&($rating>=$source['last_rating_grow']+$bonus_for_rating_diff)&&($source['album']<>100)&&($source['mod']==1)){
				$q="update LOW_PRIORITY ".$base." set votes=".$votes.", rating=".$rating.", last_mark='".$mark."', last_mark_time=".time().", last_rating_grow=".$rating." where num=".$t_key;
				$r=mysql_query($q);
				is_db_error($r);
				$q="update forum_users set bonus=bonus+1 where id=".$owner['id'];
				$r=mysql_query($q);
				is_db_error($r);
			}
			else{
				$q="update LOW_PRIORITY ".$base." set votes=".$votes.", rating=".$rating.", last_mark='".$mark."', last_mark_time=".time()." where num=".$t_key;
				$r=mysql_query($q);
				is_db_error($r);
			}

			//EVANTS --------
			if($mark=="+")$mark=2;
			else
			if($mark=="++")$mark=3;
			else $mark=1;
			if($base=="users_fotos"){
			  $q="select owner,path from users_fotos where num=".$t_key;
			  $r=mysql_query($q);
			  $path=mysql_result($r,0,"path");
			  $owner=mysql_result($r,0,"owner");
			  $query="insert into evants(id1,name2,element,type,amount,time,element_id) values(".$id.",'".$owner."','small/".$path."',6,".$mark.",".time().",".$t_key.")";
			  $result=mysql_query($query);
			  //новый тип оповещений через новости
			   if($id!=$owner_row['id']){
				  $query="insert into evants(id1, id2, name2,element,type,amount,time,element_id) values(".$id.", '".$owner_row['id']."', '".$owner."','small/".$path."',600,".$mark.",".time().",".$t_key.")";
				  $result=mysql_query($query);
			  }

			  //SEND REPORT
			  $q="select name,report,email from forum_users where name='".$owner."'";
			  $r=mysql_query($q);
			  $mail_user=mysql_fetch_array($r);

			  if($owner!=$valid_user_name){
				  $msg="Здравствуйте, ".$owner."!
				  К Вашей личной фотографии поступили новые оценки. Для просмотра скопируйте нижеприведенную ссылку  в адресную строку Вашего браузера:
				  http://www.shax-dag.ru/comments2.php?t_key=".$t_key."&base=usersfotos


				  Если же Вы не хотите следить за оценками к своим фотографиям, отключите оповещение на личной странице.
				  --------------------------------------
				  Шах-Даг портал - http://www.shax-dag.ru
				  "
				  ;
				  $msg=addslashes($msg);
				  $time=time();
				  $type=6;
				  add_in_mail_list($msg,$mail_user['email'],$time,$owner,2,$type);
			  }
			  //отправляем в рилплексор
				$rpl = new Dklab_Realplexor(
					"127.0.0.1", // host at which Realplexor listens for incoming data
					"6969",     // incoming port (see IN_ADDR in dklab_realplexor.conf)
					"users"        // namespace to use (optional)
				);
				//события
				$rpl->send("site_evants", array("access_rule"=>$user_access_rules['rules_evants'], "from" => array("name"=>iconv('windows-1251', 'UTF-8',$valid_user_name), "foto"=>$user_avatar, "id"=>$id, "gender"=>iconv('windows-1251', 'UTF-8',$user_gender)), "data"=>array("timestamp"=>time(), "type"=>"vote_".$base, "params"=>array("mark"=>$mark, "path"=>$path, "owner"=>$owner, "t_key"=>$t_key))));
			}
			else
			if($base=="fotos"){
			  $q="select path,owner from fotos where num='".$t_key."'";
			  $r=mysql_query($q);
			  $path=mysql_result($r,0,"path");
			  $owner=mysql_result($r,0,"owner");
			  $query="insert into evants(id1,element,type,amount,time,element_id) values('".$id."','small/".$path."',8,'".$mark."',".time().",'".$t_key."')";
			  $result=mysql_query($query);
			  //новый тип оповещений через новости
			  if($id!=$owner_row['id']){
				  $query="insert into evants(id1,id2, element,type,amount,time,element_id) values('".$id."', '".$owner_row['id']."', 'small/".$path."',800,'".$mark."',".time().",'".$t_key."')";
				  $result=mysql_query($query);
			  }
			  //SEND REPORT
			  $q="select name,report,email from forum_users where name='".$owner."'";
			  $r=mysql_query($q);
			  $mail_user=mysql_fetch_array($r);

			  if($owner!=$valid_user_name){
				  $msg="Здравствуйте, ".$owner."!
				  К Вашей фотографии в фотогалерее поступили новые оценки. Для просмотра скопируйте нижеприведенную ссылку  в адресную строку Вашего браузера:
				  http://www.shax-dag.ru/comments.php?t_key=".$t_key."&base=fotos


				  Если же Вы не хотите следить за оценками к своим фотографиям, отключите оповещение на личной странице.
				  --------------------------------------
				  Шах-Даг портал - http://www.shax-dag.ru
				  "
				  ;
				  $msg=addslashes($msg);
				  $time=time();
				  $type=6;
				  add_in_mail_list($msg,$mail_user['email'],$time,$owner,1,$type);
			  }
				$rpl = new Dklab_Realplexor(
					"127.0.0.1", // host at which Realplexor listens for incoming data
					"6969",     // incoming port (see IN_ADDR in dklab_realplexor.conf)
					"users"        // namespace to use (optional)
				);
				//события
				$rpl->send("site_evants", array("access_rule"=>$user_access_rules['rules_evants'], "from" => array("name"=>iconv('windows-1251', 'UTF-8',$valid_user_name), "foto"=>$user_avatar, "id"=>$id, "gender"=>iconv('windows-1251', 'UTF-8',$user_gender)), "data"=>array("timestamp"=>time(), "type"=>"vote_".$base, "params"=>array("mark"=>$mark, "path"=>$path, "owner"=>$owner, "t_key"=>$t_key))));
			}
			else
			if($base=="music"){
			  $q="select * from music where num='".$t_key."'";
			  $r=mysql_query($q); 
			  $artist=mysql_result($r,0,"artist");
			  $title=mysql_result($r,0,"title");
			  $path=mysql_result($r,0,"path");
			  $query="insert into evants(id1,element,type,amount,time,element_id, name2) values('".$id."','".$artist." - ".$title."',9,'".$mark."',".time().",'".$t_key."', '".$path."')";
			  $result=mysql_query($query);
			  //новый тип оповещений через новости
			  if($id!=$owner_row['id']){
				  $query="insert into evants(id1, id2, element,type,amount,time,element_id, name2) values('".$id."', '".$owner_row['id']."', '".$artist." - ".$title."',900,'".$mark."',".time().",'".$t_key."', '".$path."')";
				  $result=mysql_query($query);
			  }
			  //realplexor evants
			  $rpl = new Dklab_Realplexor(
					"127.0.0.1", // host at which Realplexor listens for incoming data
					"6969",     // incoming port (see IN_ADDR in dklab_realplexor.conf)
					"users"        // namespace to use (optional)
				);
				//события
				$rpl->send("site_evants", array("access_rule"=>$user_access_rules['rules_evants'], "from" => array("name"=>iconv('windows-1251', 'UTF-8',$valid_user_name), "foto"=>$user_avatar, "id"=>$id, "gender"=>iconv('windows-1251', 'UTF-8',$user_gender)), "data"=>array("timestamp"=>time(), "type"=>"vote_".$base, "params"=>array("mark"=>$mark, "artist_title"=>iconv('windows-1251', 'UTF-8',$artist." - ".$title), "t_key"=>$t_key))));
			}
			else
			if($base=="clips"){
			  $q="select artist,title,path from clips where num='".$t_key."'";
			  $r=mysql_query($q);
			  $artist=mysql_result($r,0,"artist");
			  $title=mysql_result($r,0,"title");
			  $path=mysql_result($r,0,"path");
			  $query="insert into evants(id1,type,amount,time,element,element_id) values('".$id."',10,'".$mark."',".time().",'".$path."','".$t_key."')";
			  $result=mysql_query($query);
			  //новый тип оповещений через новости
			  if($id!=$owner_row['id']){
				  $query="insert into evants(id1, id2, type,amount,time,element,element_id) values('".$id."', '".$owner_row['id']."',1000,'".$mark."',".time().",'".$path."','".$t_key."')";
				  $result=mysql_query($query);
			  }
			  //realplexor evants
			  $rpl = new Dklab_Realplexor(
					"127.0.0.1", // host at which Realplexor listens for incoming data
					"6969",     // incoming port (see IN_ADDR in dklab_realplexor.conf)
					"users"        // namespace to use (optional)
				);
				//события
				$rpl->send("site_evants",   array("access_rule"=>$user_access_rules['rules_evants'], "from" => array("name"=>iconv('windows-1251', 'UTF-8',$valid_user_name), "foto"=>$user_avatar, "id"=>$id, "gender"=>iconv('windows-1251', 'UTF-8',$user_gender)), "data"=>array("timestamp"=>time(), "type"=>"vote_".$base, "params"=>array("mark"=>$mark, "artist_title"=>iconv('windows-1251', 'UTF-8',$artist." - ".$title), "path"=>$path, "t_key"=>$t_key))));
			}
			else
			if($base=="books"){
			  $q="select artist,title from books where num='".$t_key."'";
			  $r=mysql_query($q);
			  $artist=mysql_result($r,0,"artist");
			  $title=mysql_result($r,0,"title");
			  $query="insert into evants(id1,element,type,amount,time,element_id) values('".$id."','".$artist." - ".$title."',12,'".$mark."',".time().",'".$t_key."')";
			  $result=mysql_query($query);
			} 

			//-----------
			//выводим сообщение
			
			$method_status= array ("auth_status" => "success", 
				"method_status" => "success", 
				"vote" => array("mark" => $add_value, "rating" => $rating)
				);
			echo json_encode($method_status);												
		}
	}
	else
	if($method == "getMarks"){
		$q="select * from vote_base where base='".$base."' and t_key='".addslashes($t_key)."'";
		$r=mysql_query($q);
		$rating=0;
		if($total=mysql_num_rows($r)){
			for($i=0;$i<$total;$i++){
			   $who=mysql_fetch_array($r);
			   $marks[] = array("mark" => $who['mark'], "user" => Mob_UserInfo($who['id'], "tiny", ""));	
				if($who['mark'] =="+")
					$rating++;
				else
				if($who['mark'] =="-")
					$rating--;
				else
				if($who['mark'] =="++")
					$rating = $rating+5;
			}
		}
		$method_status= array ("auth_status" => "success", 
				"method_status" => "success", 
				"rating" => $rating,
				"votes" => $marks
				);
		echo json_encode($method_status);
	}
}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
	}