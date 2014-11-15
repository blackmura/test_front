<?
session_start();
require_once("../../sql_func.php");
require_once("../../libs/users.php");
require_once("../../libs/sources.php");
require_once("../../classes/word.php");
require_once ("../../Comet/Realplexor.php");
require_once ("m_lib/m_lib.php");
sql_connect();
$ObjSessionResult = mob_restore_session($PHPSESSID, $PHPUSERID);
$valid_user_name=$ObjSessionResult["valid_user_name"];
if($valid_user_name){
	$MyObj = Mob_UserInfo(0, "tiny", $valid_user_name);
	if(!$last) $last=0;
	if(!$el_per_page) $el_per_page=16;
	else
	if($el_per_page>60) $el_per_page=60;
	if(!check_integer($last) || !check_integer($el_per_page))
		mob_die("Не верные ограничители");
	
	$added=0;
	if($method=="getMusic"){
		if(!$last) $last=0;
		if(!$el_per_page) $el_per_page=16;
		else
		if($el_per_page>60) $el_per_page=60;
		if($_GET['searchterm'])
			$searchterm = utf2win1251($_GET['searchterm']);
			
		if($user_id){
			if($_GET['initial']==1){
				//объект пользователя
				if($user_id!=$MyObj['id'])
					$UserObj = Mob_UserInfo($user_id, "tiny", "");
				else
					$UserObj=$MyObj;
			}
				
			if($searchterm){
				$sql_st=" and match(m.artist, m.title) against ('".addslashes($searchterm)."')";						
			}
			else
			$sql_st="";
			if($_GET['nation_id'])
				$sql_nation = " and m.album = '".intval($_GET['nation_id'])."'";
			else
				$sql_nation = "";
			if($_GET['order']==1){ //last commented
				$sql_order = " order by m.last_com_num desc";
			}
			else
			if($_GET['order']==2){ //rating
				$sql_order = " order by m.rating desc";
			}
			else
			if($_GET['order']==3){ //by total com
				$sql_order = " order by m.total_com desc";
			}
			else{
				$sql_order = " order by u.time desc";
			}

			$q="select  SQL_CALC_FOUND_ROWS m.* 
				from unit_sources u, 
					music m 
					where u.u_key='".addslashes($user_id)."' 
					and u.s_base='music' 
					and u.u_base='forum_users' 
					and u.s_key=m.num 
					".$sql_st."
					".$sql_nation."
					".$sql_order."
					limit ".intval($last).", ".intval($el_per_page);
			
			
			if($user_id==$MyObj['id'])
				$added=1;			
		}
		else{
			if($searchterm){
				$sql_st=" and match(m.artist, m.title) against ('".addslashes($searchterm)."')";
						
			}
			else
			$sql_st="";
			if($_GET['nation_id'])
				$sql_nation = " and m.album = '".intval($_GET['nation_id'])."'";
			else
				$sql_nation = "";
			if($_GET['order']==1){ //last commented
				$sql_order = " order by m.last_com_num desc";
			}
			else
			if($_GET['order']==2){ //rating
				$sql_order = " order by m.rating desc";
			}
			else
			if($_GET['order']==3){ //by total com
				$sql_order = " order by m.total_com desc";
			}
			else{
				$sql_order = " order by m.num desc";
			}
			if($_GET['theme_all']){
				$sql_theme = "";
			}
			else
				$sql_theme = " and m.album<>100";
			$q="select  SQL_CALC_FOUND_ROWS m.* 
			from music m 
			where m.mod=1 
			".$sql_st."
			".$sql_nation."
			".$sql_theme."
			".$sql_order."
			limit ".$last.", ".$el_per_page;
		}
		$r=mysql_query($q);
		is_db_error($r);
		$r3=mysql_query("SELECT FOUND_ROWS()");
		$total_all=mysql_result($r3,0);
		$total_music=mysql_num_rows($r);
		$ObjMusics=Array();
		for($i=0;$i<$total_music;$i++){
			$row=mysql_fetch_array($r);
			$ObjMusics[$i] = array(
				"num" => $row['num'], 
				"artist" => iconv("Windows-1251","UTF-8",$row['artist']), 
				"title" => iconv("Windows-1251","UTF-8",$row['title']), 
				"path" => $row['path'],
				"rating" => $row['rating'],
				"comments" => $row['total_com'],
				"lang" => $row['album'],
				"added" => $added);
		}
		
		$RESPONSE=json_encode(array (
			"auth_status"=>"success", 
			"method_status"=>"success",
			"musics" => $ObjMusics, 			
			"total_all" => $total_all, 
			"user" => $UserObj
			));			
			
		
		echo $RESPONSE; 
	}

}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
}