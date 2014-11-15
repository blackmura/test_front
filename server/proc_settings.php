<?
session_start();
include("../../sql_func.php");
include "m_lib/m_lib.php";
sql_connect();
/*ÍÅ ÎÏÒÈÌÈÇÈĞÎÂÀÍ. ÈÑÏÎËÜÇÎÂÀÒÜ ÎÁÚÅÊÒ MYOBJ ÂÌÅÑÒÎ ÏÎÂÒÎĞÍÛÕ ÇÀÏĞÎÑÎÂ Ê ÁÀÇÅ*/
$ObjSessionResult = mob_restore_session($PHPSESSID, $PHPUSERID);
$valid_user_name=$ObjSessionResult["valid_user_name"];
if($valid_user_name){
	$MyObj = Mob_UserInfo(0, "tiny", $valid_user_name);
	$my_id=$MyObj["id"];
	if($_POST['method']=="setAvatar"){
		$MAX_FILE_SIZE=10485760; //10mb
		if($_FILES["userfile"]){
			if((detect_format($_FILES["userfile"]['name'])!="jpg")&&(detect_format($_FILES["userfile"]['name'])!="JPG")&&(detect_format($_FILES["userfile"]['name'])!="jpeg")&&(detect_format($_FILES["userfile"]['name'])!="gif")&&(detect_format($_FILES["userfile"]['name'])!="GIF")&&(detect_format($_FILES["userfile"]['name'])!="png")&&(detect_format($_FILES["userfile"]['name'])!="PNG")){
			   mob_die("Íå ïğàâèëüíûé ôîğìàò èçîáğàæåíèÿ");
			}
			if(!$_FILES["userfile"]['size']){
			   mob_die("Ïóñòîé ôàéë");
			}
			if($_FILES["userfile"]['size']>$MAX_FILE_SIZE){
			   mob_die("Ôàéë ïğåâûøàåò äîïóñòèìûé ğàçìåğ 10Ìá");
			}
			if(!is_uploaded_file($_FILES["userfile"]['tmp_name'])){
			    mob_die("Îøèáêà HTTP POST");
			}
			sql_connect();
			$query="select foto, id from forum_users  where name='".$valid_user_name."'";
			$result=mysql_query($query);
			is_db_error($result);
			$old_foto=mysql_result($result,0,"foto");
			$my_id=mysql_result($result,0,"id");
			$cluster_dir=date("Y-m");
			$path="../../avatars/".$cluster_dir; $path_no_cluster="../../avatars";
			$add=rand(0,100);
			$userfile_name=$my_id."_".$add.".".detect_format($userfile_name);
			$userfile_name_for_sql=$cluster_dir."/".$userfile_name;
			$upfile=$path."/".$userfile_name;
			//åñëè íåò äèğåêòîğèè
			if(!file_exists($path)){
				mkdir($path);
			}
			if(!copy($_FILES["userfile"]['tmp_name'],$upfile)){
			   mob_die("Îøèáêà ïğè êîïèğîâàíèè ôàéëà ".$_FILES["userfile"]['tmp_name']);
			}

			if($old_foto!=''){
						 if(file_exists("../../avatars/".$old_foto)) 
							unlink("../../avatars/".$old_foto);
						 if(file_exists("../../avatars/small/".$old_foto))
							unlink("../../avatars/small/".$old_foto);
						 if(file_exists("../../avatars/small/small/".$old_foto))
							unlink("../../avatars/small/small/".$old_foto);
						 if(file_exists("../../avatars/small_100/".$old_foto))
							unlink("../../avatars/small_100/".$old_foto);
						 if(file_exists("../../avatars/small_150/".$old_foto))
							unlink("../../avatars/small_150/".$old_foto);
						 if(file_exists("../../avatars/large_800/".$old_foto))
							unlink("../../avatars/large_800/".$old_foto);
						 
			}
			$query="update forum_users set foto='".$userfile_name_for_sql."', guests=1 where id='".addslashes($my_id)."'";
			$result=mysql_query($query);
			is_db_error($result);
			$query="select * from evants where id1=".$my_id." and type=5 and time>".$TODAYS_MIDNIGHT_TIME;
			$result=mysql_query($query);
			if(mysql_num_rows($result)>0){
				$ev_row=mysql_fetch_array($result);
				$query="update evants set element='small_100/".$userfile_name_for_sql."', time=".time()." where type=5 and time=".$ev_row['time']." and id1='".addslashes($my_id)."'";
				$result=mysql_query($query);
				is_db_error($result); 
			}
			else{
				$query="insert into evants(id1,element,type,time) values(".$my_id.",'small_100/".$userfile_name_for_sql."',5,".time().")";
				$result=mysql_query($query);
			}
			is_db_error($result);
			//Ñîçäàåì ìèíèàòşğû

			$new_path=$path_no_cluster."/large_800/".$cluster_dir; 
			if(!file_exists($new_path)){
				mkdir($new_path);
			}
			$resize_success=resize_foto($userfile_name,800,$path, $new_path); 

			$new_path=$path_no_cluster."/".$cluster_dir;
			if(!file_exists($new_path)){
				mkdir($new_path);
			}
			$resize_success=resize_foto($userfile_name,195,$path,$new_path);

			$new_path=$path_no_cluster."/small_150/".$cluster_dir;
			if(!file_exists($new_path)){
				mkdir($new_path);
			}
			$resize_success=resize_foto($userfile_name,150,$path,$new_path);

			$new_path=$path_no_cluster."/small_100/".$cluster_dir;
			if(!file_exists($new_path)){
				mkdir($new_path);
			}
			$resize_success=resize_foto($userfile_name,100,$path,$new_path);

			$new_path=$path_no_cluster."/small/".$cluster_dir;
			if(!file_exists($new_path)){
				mkdir($new_path);
			}
			$resize_success=resize_foto($userfile_name,60,$path,$new_path);

			$new_path=$path_no_cluster."/small/small/".$cluster_dir;
			if(!file_exists($new_path)){
				mkdir($new_path);
			}
			$resize_success=resize_foto($userfile_name,40,$path,$new_path);
			$method_status = array( "auth_status"=>"success", "method_status"=>"success", "src" => $userfile_name_for_sql);
			echo json_encode($method_status);
		}
	}
	else
		mob_die("Îøèáêà â ìåòîäå");

}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
}