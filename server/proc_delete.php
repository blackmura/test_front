<?
session_start();
include("../../sql_func.php");
include("../../libs/nations.php");
include("../../libs/sources.php");
include "m_lib/m_lib.php";
sql_connect();
$ObjSessionResult = mob_restore_session($PHPSESSID, $PHPUSERID);
$valid_user_name=$ObjSessionResult["valid_user_name"];
if($valid_user_name){
	$MyObj = Mob_UserInfo(0, "tiny", $valid_user_name);
	$my_id=$MyObj["id"];
	
	if(!check_integer($track_num)&&($base!="messages")&&($base!="comments")&&($base!="comments2")){
		$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Oops! Не корректный параметр атрибута."));
		echo json_encode($method_status);
		exit;
	}
	//фотки
	if(($base=="users_fotos")||($base=="fotos")){
		$query="select * from ".$base." where num=".$track_num." and owner='".$valid_user_name."'";
		$result=mysql_query($query);
		$row=mysql_fetch_array($result);
		$valid=mysql_num_rows($result);
		if(!$valid){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Oops! Объект не принадлежит Вам."));
			echo json_encode($method_status);
			exit;
		}
		user_source_deleting ($track_num, $base, 0, 0);
		$method_status= array ("auth_status" => "success", "method_status" => "success", "text" => iconv("Windows-1251","UTF-8", "Удалено"));
		echo json_encode($method_status);
	}
	//комменты
	else if(($base=="comments")||($base=="comments2")){
	   $query="select num, t_key, name, base from ".mysql_escape_string($base)." where num in (".addslashes($track_num).")";
	   $result2=mysql_query($query);
	   $total=mysql_num_rows($result2);
	   is_db_error($result2);
	   for($i=0;$i<$total;$i++){ 
		   $comments=mysql_fetch_array($result2);
		   if(($comments['base']=="fotos")|| ($comments['base']=="music") || ($comments['base']=="users_fotos") || ($comments['base']=="clips") || ($comments['base']=="books")){
			   $query="select owner from ".$comments['base']." where num=".$comments['t_key'];
			   $r=mysql_query($query);
			   $source=mysql_fetch_array($r);
			   if(($source['owner']!=$valid_user_name)&&($valid_user_name!=$comments['name'])){
					$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Oops! Объект не принадлежит Вам."));
					echo json_encode($method_status);
					exit;
				}
			   user_source_deleting ($comments['num'], $base, 0, 0);
			  
				
		   }
		   else
		   if($comments['base']=="group_board"){
				$query="select user_type from group_users where group_id=".$comments['t_key']." and user_id=".$my_id;
				$result=mysql_query($query);
				if(mysql_num_rows($result)>0)
					$my_row_ingroup=mysql_fetch_array($result);
				if(($my_row_ingroup['user_type']!="1")&&($my_row_ingroup['user_type']!="2")&&($valid_user_name!=$comments['name'])){
					$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Oops! Объект не принадлежит Вам."));
					echo json_encode($method_status);
					exit;
				}
				user_source_deleting ($comments['num'], $base, 0, 0);
				
		   }
		   else
		   if($comments['base']=="topics"){
				$query="select owner, base, t_key from ".$comments['base']." where num=".$comments['t_key'];
				$result=mysql_query($query);
				$source=mysql_fetch_array($result);
				if($source['base']=="groups"){
					$query="select user_type from group_users where group_id=".$source['t_key']." and user_id=".$my_id;
					$result=mysql_query($query);
					if(mysql_num_rows($result)>0)
						$my_row_ingroup=mysql_fetch_array($result);
					if(($my_row_ingroup['user_type']!="1")&&($my_row_ingroup['user_type']!="2")&&($source['owner']!=$valid_user_name)&&($valid_user_name!=$comments['name'])){
						$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Oops! Объект не принадлежит Вам."));
						echo json_encode($method_status);
						exit;
					}
					user_source_deleting ($comments['num'], $base, 0, 0);
					
				}
		   }
		   else
		   if($comments['base']=="unit_sources"){
				$query="select * from ".$comments['base']." where num=".$comments['t_key'];
				$result=mysql_query($query);
				$source=mysql_fetch_array($result);
				if($source['u_base']=="groups"){
					$query="select user_type from group_users where group_id=".$source['u_key']." and user_id=".$my_id;
					$result=mysql_query($query);
					if(mysql_num_rows($result)>0)
						$my_row_ingroup=mysql_fetch_array($result);
					if(($my_row_ingroup['user_type']!="1")&&($my_row_ingroup['user_type']!="2")&&($source['add_username']!=$valid_user_name)&&($valid_user_name!=$comments['name'])){
						$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Oops! Объект не принадлежит Вам."));
						echo json_encode($method_status);
						exit;
					}
					
					user_source_deleting ($comments['num'], $base, 0, 0);
					
				}
		   }
		   else
		   if($comments['base']=="shares"){
				$query="select user_id from ".$comments['base']." where num=".$comments['t_key'];
				$result=mysql_query($query);
				$source=mysql_fetch_array($result);
				if(($source['user_id']==$my_id) || ($comments['name']==$valid_user_name)){
					$q="delete from comments2 where num='".addslashes($track_num)."'";
					$r=mysql_query($q);
					
				}
		    }
	   }
	   $method_status= array ("auth_status" => "success", "method_status" => "success", "text" => iconv("Windows-1251","UTF-8", "Удалено"));
	   echo json_encode($method_status);
	}
	else if($base=="messages"){
		 //-- MSG NEW
		 if($track_num!="all"){
			$q="update msg_in set del=1 where num in (".addslashes($track_num).") and retrievers_id=".$my_id;
			$r=mysql_query($q);
			is_db_error($r);
	
			$q="update msg_out set del=1 where num in (".addslashes($track_num).") and senders_id=".$my_id;
			$r=mysql_query($q);
			is_db_error($r);
		 
		 $method_status= array ("auth_status" => "success", "method_status" => "success", "text" => iconv("Windows-1251","UTF-8", "Удалено"));
		  echo json_encode($method_status);
		 //---
	   }
	   else
	   if(($track_num=="all")&&$track_num2){
		  //--- MSG NEW
		  $q="update msg_out set del=1 where senders_id=".$my_id." and retrievers_id='".addslashes($track_num2)."'";
		  $r=mysql_query($q);
		  echo mysql_error();
		  $q="update msg_in set del=1 where retrievers_id=".$my_id." and senders_id='".addslashes($track_num2)."'";
		  $r=mysql_query($q);
		  $method_status= array ("auth_status" => "success", "method_status" => "success", "text" => iconv("Windows-1251","UTF-8", "Удалено"));
		  echo json_encode($method_status);
		  //----
	   }
	   else
	   if($track_num=="all"){
		  //--- MSG NEW
		  $q="update msg_out set del=1 where senders_id=".$my_id;
		  $r=mysql_query($q);
		  echo mysql_error();
		  $q="update msg_in set del=1 where retrievers_id=".$my_id;
		  $r=mysql_query($q);
		  $method_status= array ("auth_status" => "success", "method_status" => "success", "text" => iconv("Windows-1251","UTF-8", "Удалено"));
		  echo json_encode($method_status);
		  //--
		}
	}
	else if($base=="fotolib"){                                            //COMMENTS priv
	   $query="select t_key,name from comments2 where num=".$track_num;
	   $result=mysql_query($query);
	   $comments=mysql_fetch_array($result);
	   $query="select owner from users_fotos where num=".$comments['t_key'];
	   $result=mysql_query($query);
	   $fotos=mysql_fetch_array($result);
	   if(($fotos['owner']!=$valid_user_name)&&($valid_user_name!=$comments['name'])){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Oops! Объект не принадлежит Вам."));
			echo json_encode($method_status);
			exit;
	   }
	   $q="delete from comments2 where num=".$track_num;
	   $r=mysql_query($q);
	   $method_status= array ("auth_status" => "success", "method_status" => "success", "text" => iconv("Windows-1251","UTF-8", "Удалено"));
		echo json_encode($method_status);
	}
	// ѕодарки
	else if($base=="gifts"){
		$q="select * from users_gifts where num=".$track_num;
		$r=mysql_query($q);
		$gifts=mysql_fetch_array($r);
		if($gifts['user_id'] == $my_id){
		   $q="delete from users_gifts where num=".$track_num;
		   $method_status= array ("auth_status" => "success", "method_status" => "success", "text" => iconv("Windows-1251","UTF-8", "Удалено"));
			echo json_encode($method_status);
		}
	}
	else
	if($base=="topics"){
		$q="select base, t_key, owner from topics where num='".addslashes($track_num)."'";
		$r=mysql_query($q);
		echo mysql_error();
		is_no_result($r);
		$topic_row=mysql_fetch_array($r);
		if($topic_row['base']=="groups"){
			$q="select user_type from group_users where user_id='".addslashes($my_id)."' and group_id='".addslashes($topic_row['t_key'])."'";
			$r=mysql_query($q);
			$gr_user_row=mysql_fetch_array($r);
			if(($gr_user_row['user_type']==1)||($gr_user_row['user_type']==2)||($topic_row['owner']==$valid_user_name)){
				user_source_deleting ($track_num, $base, "", "");
				 $method_status= array ("auth_status" => "success", "method_status" => "success", "text" => iconv("Windows-1251","UTF-8", "Удалено"));
				echo json_encode($method_status);
			}
			else{
				$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Oops! Объект не принадлежит Вам."));
				echo json_encode($method_status);
				exit;
			}
		}	
	}
	else
	if($base=="share_links"){
		//добавить проверку : если удален последний подписчик - удалить шару и все комменты
		$q="select s.user_id as owner, l.user_id as linked  from shares s, share_links l where s.num='".addslashes($share_id)."' and l.share_id=s.num and l.user_id='".addslashes($track_num)."'";
		$r=mysql_query($q);
		if(mysql_num_rows($r)>0){
			$share_row=mysql_fetch_array($r);
			if(($share_row['owner']==$my_id) || ($share_row['linked']==$my_id)){
				$q="delete from share_links where user_id='".addslashes($track_num)."' and share_id='".addslashes($share_id)."'";
				$r=mysql_query($q);
				$method_status= array ("auth_status" => "success", "method_status" => "success", "text" => iconv("Windows-1251","UTF-8", "Удалено"));
				echo json_encode($method_status);
			}
		}
	}
	else{
		$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Не определен метод"));
		echo json_encode($method_status);
		exit;
	}
}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
	}

