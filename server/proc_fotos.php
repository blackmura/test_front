<?
session_start();
require_once("../../sql_func.php");
require_once ("m_lib/m_lib.php");
sql_connect();
$ObjSessionResult = mob_restore_session($PHPSESSID, $PHPUSERID);
$valid_user_name=$ObjSessionResult["valid_user_name"];
if($valid_user_name){
	$MyObj = Mob_UserInfo(0, "tiny", $valid_user_name);
	$my_id=$MyObj["id"];
	if($method=="getUserFotos"){
		if(!$user_id){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Не передан параметр user_id"));
			echo json_encode($method_status);
			exit;
		}
		else{
			if(!$last) $last=0;
			if(!$el_per_page) $el_per_page=16;
			else
			if($el_per_page>60) $el_per_page=60;
			
			$ObjUser = Mob_UserInfo($user_id, "tiny", "");
			//проверка на дружбу
			$q="select 1 from friends where f_id1=".$ObjUser['id']." and f_wait=0 and f_id2='".$my_id."' or f_id2='".$ObjUser['id']."' and f_wait=0 and f_id1='".$my_id."'";
			$r=mysql_query($q);;
			if(mysql_num_rows($r)>0) $is_friend=1;
			else $is_friend=0;
			
			if($my_id==$user_id){
				$sql_access_postfix=" ";
			}
			else
			if($is_friends){
				$sql_access_postfix=" and (ualb.access is NULL or ualb.access in(1,2))";
			}
			else{
				$sql_access_postfix=" and (ualb.access is NULL or ualb.access=1)";
			}
			$q="select  SQL_CALC_FOUND_ROWS t.* from
				(
					select 
					uf.num,
					uf.title,
					uf.path,
					uf.rating,
					NULL as total_com,
					1 as private_foto
					from users_fotos uf
						left outer join users_albums ualb  on uf.album_id=ualb.num  
					where uf.owner='".$ObjUser['login']."' 
					".$sql_access_postfix." 
					
					union
					
					select 
					f.num,
					f.title,
					f.path,
					f.rating,
					f.total_com,
					0 as private_foto
					from fotos f
					where f.owner='".$ObjUser['login']."'
				) t
				limit ".$last.", ".$el_per_page;
			$r=mysql_query($q);
			is_db_error($r);
			$r3=mysql_query("SELECT FOUND_ROWS()");
			$total_all=mysql_result($r3,0);
			$pict_total=mysql_num_rows($r);
			$ObjFotosArr = Array();
			for($i=0;($i<$pict_total);$i++){
				$pict_row=mysql_fetch_array($r);
				if($pict_row['private_foto']){
					$q="select sum(1) from comments2 where t_key =".$pict_row['num']." and base='users_fotos'";
					$r4=mysql_query($q);
					$pict_row['total_com'] = mysql_result($r4,0); 
					
				}
				$ObjFotosArr[]=array(
					"num" => $pict_row['num'], 
					"title" => iconv("Windows-1251","UTF-8",$pict_row['title']), 
					"path" => $pict_row['path'], 
					"rating" => $pict_row['rating'],
					"comments" => $pict_row['total_com'],
					"private_foto" => $pict_row['private_foto']
					);
			}
			$ObjFotos = array ("total" => $pict_total, "total_all" => $total_all, "fotos" => $ObjFotosArr);
			
			
			if($initial!=1){
				$ObjUser=null;
			}
				
			$RESPONSE=json_encode(array (
				"auth_status"=>"success", "method_status"=>"success",
				"user" => $ObjUser, 
				"fotos" => $ObjFotos, 
				));
			echo $RESPONSE; 
			
		}
	}
	else
	if($method=="getGalleryFotos"){
		if(!$last) $last=0;
		if(!$el_per_page) $el_per_page=16;
		else
		if($el_per_page>60) $el_per_page=60;
		

		$sql_where = "";
		$sql_where.= $nation_id!=null ? "and uf.user_nation='".addslashes($nation_id)."'" : "";
		$sql_where.= $gender!=null ? " and uf.website='".addslashes(utf2win1251($gender))."'" : "";
		$sql_where.= $album!=null ? " and f.album='".addslashes($album)."'" : "";
		
		$q="select  SQL_CALC_FOUND_ROWS f.*, uf.id as user_id 
		from fotos f left join forum_users uf on uf.name=f.owner 
		where f.`mod`=1 
		".$sql_where." 
		order by f.num desc  limit ".$last.", ".$el_per_page;
		
		$r=mysql_query($q);
		is_db_error($r);
		$r3=mysql_query("SELECT FOUND_ROWS()");
		$total_all=mysql_result($r3,0);
		$pict_total=mysql_num_rows($r);
		$ObjFotosArr = Array();
		for($i=0;($i<$pict_total);$i++){
			$pict_row=mysql_fetch_array($r);
			$ObjFotosArr[]=array("num" => $pict_row['num'], 
				"title" => iconv("Windows-1251","UTF-8",$pict_row['title']), 
				"path" => $pict_row['path'], 
				"rating" => $pict_row['rating'],
				"comments" => $pict_row['total_com'], 
				"user" => Mob_UserInfo($pict_row['user_id'], "tiny",null ),
				"private_foto" => 0 
				);
		}
		$ObjFotos = array ("total" => $pict_total, "total_all" => $total_all, "fotos" => $ObjFotosArr);
		
		$RESPONSE=json_encode(array (
			"auth_status"=>"success", "method_status"=>"success",
			"fotos" => $ObjFotos, 
			));
		echo $RESPONSE; 
		
	}
	

}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
}