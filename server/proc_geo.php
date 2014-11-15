<?
session_start();
include("../../sql_func.php");
include "m_lib/m_lib.php";
sql_connect();
$ObjSessionResult = mob_restore_session($PHPSESSID, $PHPUSERID);
$valid_user_name=$ObjSessionResult["valid_user_name"];
if($valid_user_name){
	$MyObj = Mob_UserInfo(0, "tiny", $valid_user_name);
	$my_id=$MyObj["id"];
	if($method=="setLocation"){
		if($x && $y){
			$q="insert into geo (lat, longt, user_id, time) values ('".addslashes($x)."', '".addslashes($y)."', '".addslashes($my_id)."', ".time().")";
			$r=mysql_query($q);
			is_db_error($r);
			$method_status= array ("auth_status" => "success", 
				"method_status" => "success", 
				);
		}
		else
			$method_status= array ("auth_status" => "success", 
				"method_status" => "fail", 
				"error_text" => iconv("Windows-1251","UTF-8", "ќшибка в параметрах запроса ".$x." ".$y)
				);
													
		
	}
	else
	if($method=="getUsersLocation"){
		$q="select  f.*, l.lat, l.longt,
					o.status as online_status
					from 
					forum_users f left outer join online_users o on o.name=f.name
						left outer join 
						(	select g.*
							from (select max(num) as num, user_id
									from geo g2
									group by user_id) t,
									geo g
							where g.num=t.num
						) l on l.user_id = f.id
							
					where /*o.status is not NULL
					and */l.lat is not NULL";
		$r=mysql_query($q);
		is_db_error($r);
		$total=mysql_num_rows($r);
		$ObjUsersArr= Array();
		for($i=0;$i<$total; $i++){
			$row=mysql_fetch_array($r);
			$ObjUsersArr[] = array("user" => Mob_UserInfo_by_row($row, "tiny"), "pos" => array($row['lat'], $row['longt']));
			
		}
		$method_status= array ("auth_status" => "success", 
				"method_status" => "success",
				"geo_users" => $ObjUsersArr
				);									
		
	}
	else
		$method_status= array ("auth_status" => "success", 
				"method_status" => "fail", 
				"error_text" => iconv("Windows-1251","UTF-8", "ќшибка в параметрах запроса ".$x." ".$y)
				);
	echo json_encode($method_status);	
}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
}