<? 
session_start();
require_once("../../sql_func.php");
require_once ("m_lib/m_lib.php");
require_once "../../include/sphinxapi.php";
require_once "m_lib/sphinx_lib.php";
sql_connect();
$ObjSessionResult = mob_restore_session($PHPSESSID, $PHPUSERID);
$valid_user_name=$ObjSessionResult["valid_user_name"];
if($valid_user_name){
	$MyObj = Mob_UserInfo(0, "tiny", $valid_user_name);
	$my_id=$MyObj["id"];
	if($method=="getUsersSearch"){
		if(!$name&&!$country&&!$city&&!$resp&&!$raion&&!$selo&&!$gender&&!$user_nation&&!$user_vuz_name&&!$user_vuz_faculty&&!$online){
			$new_users=1;
		}
		if(!$MyObj['nation_id']&&$user_nation){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Вы не можете искать людей по народностям, пока не укажите свою народность в профиле"));
			echo json_encode($method_status);
			exit;
		}
		
		if(!$last) $last=0;
		if(!$el_per_page) $el_per_page=16;
		else
		if($el_per_page>60) $el_per_page=60;
		//конвертируем в 1251
		$name=utf2win1251($name); $country=utf2win1251($country); $city=utf2win1251($city); $resp=utf2win1251($rest); $raion=utf2win1251($raion);
		$selo=utf2win1251($selo); $gender=utf2win1251($gender); $user_vuz_name=utf2win1251($user_vuz_name); $user_vuz_faculty=utf2win1251($user_vuz_faculty);
		//если поиск не имеет фильтра на онлайн, то ищем сфинксом
		if(!$online){
			//определяем параметра сфинкса
			if($new_users!=1)
				$sp_variant="search";
			else
				$sp_variant="new_users";

			if($name)
				$sp_params['name']=$name;
			if($country)
				$sp_params['country']=$country;
			if($city)
				$sp_params['city']=$city;
			if($resp)
				$sp_params['resp']=$resp;
			if($raion)
				$sp_params['raion']=$raion;
			if($selo)
				$sp_params['selo']=$selo;
			if($gender)
				$sp_params['website']=$gender;
			if($user_nation)
				$sp_params['user_nation']=$user_nation;
			if($user_vuz_name)
				$sp_params['vuz_name']=$user_vuz_name;
			if($user_vuz_faculty)
				$sp_params['vuz_faculty']=$user_vuz_faculty;
			//получаем список ID из сфинкса
			$result_sp=Sp_searchUsers($sp_params, $sp_variant, intval($last), intval($el_per_page));
			if($result_sp['total']) $total=count(array_keys($result_sp['matches'])); else $total=0;
			if($result_sp['total_found']) $total_all=$result_sp['total_found']; else $total_all=0;
			$ObjUsersArr=Mob_UserInfo(array_keys($result_sp['matches']), "medium", null);
			$ObjUsers = array ("total" => $total, "total_all" => $total_all, "users" => $ObjUsersArr);	
		}
		else{
			$sql_where=" o.status is not NULL";
			if($name)
				$sql_where.=" and f.name like '%".addslashes($name)."%'";
			if($country)
				$sql_where.=" and f.country like '%".addslashes($country)."%'";
			if($city)
				$sql_where.=" and f.city like '%".addslashes($city)."%'";
			if($resp)
				$sql_where.=" and f.resp like '%".addslashes($resp)."%'";
			if($raion)
				$sql_where.=" and f.raion like '%".addslashes($raion)."%'";
			if($selo)
				$sql_where.=" and f.selo like '%".addslashes($selo)."%'";
			if($gender)
				$sql_where.=" and f.website ='".addslashes($gender)."'";
			if($user_nation)
				$sql_where.=" and f.user_nation ='".addslashes($user_nation)."'";
			if($user_vuz_name)
				$sql_where.=" and uf.vuz_name = '".addslashes($user_vuz_name)."'";
			if($user_vuz_faculty)
				$sql_where.=" and uf.vuz_faculty = '".addslashes($user_vuz_faculty)."'";
			$q="select SQL_CALC_FOUND_ROWS f.*,
					o.status as online_status
					from 
					forum_users f left outer join online_users o on o.name=f.name
						left outer join user_info uf on f.id=uf.id 
					where ".$sql_where."
					limit ".intval($last).", ".intval($el_per_page);
			$r=mysql_query($q);
			is_db_error($r);
			$r3=mysql_query("SELECT FOUND_ROWS()");
			$total_all=mysql_result($r3,0);
			$total=mysql_num_rows($r);
			$ObjUsersArr= Array();
			for($i=0;$i<$total;$i++){
					$row=mysql_fetch_array($r);
					//достаем объект по записи
					$ObjUsersArr[] = Mob_UserInfo_by_row($row, "medium"); 
			}
			$ObjUsers = array ("total" => $total, "total_all" => $total_all, "users" => $ObjUsersArr);	
		}
			
		$RESPONSE=json_encode(array (
			"auth_status"=>"success", "method_status"=>"success",
			"users_data" => $ObjUsers, 
			));
		echo $RESPONSE; 
			
		
	}
	else
	if($method=="getUsersSelection"){
		if(!$most_liberal&&!$most_loved&&!$new_users&&!$most_popular){
			$most_loved=1;
		}	
	}
}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
}