<?
function is_db_error($r){
	if(!$r){
		echo json_encode(array("auth_status" => "success", "method_status" => "fail", "error_text" => mysql_error()));
		exit;
	}
}
function mob_die($text){
	$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", $text));
	echo json_encode($method_status);
	exit;
}
function Mob_UserInfo_by_row($row, $format){
	
		$user_id=$row['id'];
		if($row['fio']){
			if(strlen($row['fio'])>=3)
				$user_fio=$row['fio'];
			else 
				$user_fio=$row['fio']." (".$row['name'].")";
		}
		else
			$user_fio=$row['name'];
		//нация	
		$user_nation=$row['user_nation'];
		//онлайн статус
		if($row['online_status']){
			if($row['online_status']==1)
				$RU_status_txt=iconv("Windows-1251","UTF-8","На месте");
			else{
				if($row['website']=="Ж")
				  $RU_status_txt=iconv("Windows-1251","UTF-8","Отошла...");
				else
				  $RU_status_txt=iconv("Windows-1251","UTF-8","Отошел...");
			}
			$RU_status_code=1;
			$RU_status_lastvisit=time();
		}
		else{
			$RU_status_code=0;
			$RU_status_lastvisit=$row['last_visit'];
			$last_visit=how_long_time($row['last_visit']);
			if(($row['website']=="Ж")&&$last_visit)
				$RU_status_txt=iconv("Windows-1251","UTF-8","Была ".$last_visit);
			else
			if($last_visit)
				$RU_status_txt=iconv("Windows-1251","UTF-8","Был ".$last_visit);
			else
				$RU_status_txt=iconv("Windows-1251","UTF-8","Нет на месте");
		}
		$ObjOnlineStatus=array(
					"txt" =>$RU_status_txt,
					"code" =>$RU_status_code,
					"last_visit" => $RU_status_lastvisit
					);
		//foto
		if(!$row['foto'])$user_foto="no_avatar.png";
			else $user_foto=$row['foto'];
		//login
		$login=iconv("Windows-1251","UTF-8",$row['name']);
		//gender
		$gender=iconv("Windows-1251","UTF-8",$row['website']);
		
		if($format=="tiny"){
			$UserObj = array("id" => (string)$row['id'],
				"name" => iconv("Windows-1251","UTF-8", $user_fio), 
				"city" => iconv("Windows-1251","UTF-8",$row['city']),
				"resp" => iconv("Windows-1251","UTF-8",$row['resp']),
				"foto" => $user_foto, 
				"gender" => $gender, 
				"online_status" => $ObjOnlineStatus, 
				"login"=>$login, "del"=>$row['del'], 
				"nation_id" => $user_nation );
		}
		else
		if($format == "medium"){
			//city
			$city=iconv("Windows-1251","UTF-8",$row['city']);
			$country=iconv("Windows-1251","UTF-8",$row['country']);
			//original from
			$resp=iconv("Windows-1251","UTF-8",$row['resp']);
			$raion=iconv("Windows-1251","UTF-8",$row['raion']);
			$selo=iconv("Windows-1251","UTF-8",$row['selo']);

			$UserObj = array("id" => (string)$row['id'],
				"name" => iconv("Windows-1251","UTF-8", $user_fio), 
				"foto" => $user_foto, 
				"gender" => $gender, 
				"online_status" => $ObjOnlineStatus, 
				"login"=>$login, "del"=>$row['del'], 
				"nation_id" => $user_nation,
				"resp" => $resp,
				"raion" => $raion,
				"selo" => $selo,
				"city" => $city,
				"country" => $country,
				
				);
		}
	return $UserObj;
}
function Mob_UserInfo($user_id, $format, $login){
	//краткая инфа по пользователю
	//если передан массив айдишников
	if(is_array($user_id)){
		$q="select f.*, 
			o.status as online_status
			from 
			forum_users f left outer join online_users o on o.name=f.name
			where f.id in (".implode(", ", $user_id).")
			order by FIELD(f.id, ".implode(", ", $user_id).")
			";
			
	}
	else
	if($user_id)
		$q="select f.*, 
			o.status as online_status
			from 
			forum_users f left outer join online_users o on o.name=f.name
			where f.id='".addslashes($user_id)."'";
	else
	if($login)
		$q="select f.*, 
			o.status as online_status
			from 
			forum_users f left outer join online_users o on o.name=f.name
			where f.name='".addslashes($login)."'";
	$r=mysql_query($q);
	is_db_error($r);
	$total=mysql_num_rows($r);
	if($total>0){
		for($i=0;$i<$total;$i++){
			$row=mysql_fetch_array($r);
			//достаем объект по записи
			$UserObj = Mob_UserInfo_by_row($row, $format); 
			//если возвращаем массив
			if(is_array($user_id))
				$UserObj_Return[]=$UserObj;
			else
				$UserObj_Return=$UserObj; 
		}
		return $UserObj_Return;
	}
	else
		return null;

}
function user_go_online($user_id, $valid_user_name){
	$q="select * from user_options where type=1 and value=1 and user_id='".addslashes($user_id)."'";
	$r=mysql_query($q);
	is_db_error($r);
	//если нет невидимки
	if(mysql_num_rows($r)==0){		 
					$q="select status,timestamp from online_users where name='".$valid_user_name."'";
					$r=mysql_query($q);
					is_db_error($r);
					if(mysql_num_rows($r)>0){
						$q="update LOW_PRIORITY online_users set timestamp=".time().", status=1 where name='".$valid_user_name."' ";
						$r=mysql_query($q);
						is_db_error($r);
						
					}
					else{
						mysql_query("INSERT INTO online_users(timestamp,name,ip,status) VALUES (".time().",'".$valid_user_name."','".$REMOTE_ADDR."',1)");// or die("$dbtable_users_line Database INSERT Error line") и т.п. писать в этой строке нельзя!!! из за кук.
						is_db_error($r);
					}

		
	}
}
function mob_restore_session($sessid, $user_id){
	//запускаем механизм
	//если сессия уже сдохла - пробуем поднять данные из базы
	if(!$_SESSION['valid_user_name']){
		//если все параметры есть
		if($sessid && $user_id){
			$q="select f.name from forum_users f left join user_info u on u.id=f.id 
				where f.id='".intval(addslashes($user_id))."' and u.sessid='".addslashes($sessid)."'";
			$r=mysql_query($q);
			if(mysql_num_rows($r)==1){
				//если пользователь последний раз использовал такой же id сессии, то устанавливаем новые переменные сессии
				$row=mysql_fetch_array($r);
				$valid_user_name=$row['name'];
				$_SESSION['valid_user_name']=$valid_user_name;
				//обновляем данные по входу
				$q="select 1 from user_info where id=".$user_id;
				$r=mysql_query($q);
				if(mysql_num_rows($r)>0){
					$q="update user_info set last_visit=".time().", last_ip='".$REMOTE_ADDR."', sessid='".addslashes(session_id())."' where id=".$user_id;
				}
				else{
					$q="insert into user_info(id, last_visit, last_ip, sessid) values(".$user_id.", ".time().", '".$REMOTE_ADDR."', '".addslashes(session_id())."')";
				}
				$r=mysql_query($q); 
				//обновляем инфу онлайн
				user_go_online($user_id, $valid_user_name);
				$return = array("status" => "SUCCESS: session updated: ".session_id(), "valid_user_name" => $_SESSION['valid_user_name']); 
				
			}
			else //если по какой-то причине последний раз он авторизовывался с сдругой сессией то отправляем на повторную авторизацию
				{
				$return = array("status" => "FAIL: sessid not match db", "valid_user_name"=>null); 
				}
		}
		else{
			$return = array("status" => "FAIL: incorrect params", "valid_user_name"=>null); 
		}
		
	}
	else //если повторная авторизация не требуется
			{
			$return = array("status" => "SUCCESS: session is already exists", "valid_user_name" => $_SESSION['valid_user_name']); 
			}
	return $return;
}
function evants_by_ids_array($ids_arr,$my_Obj, $last, $el_per_page){
	if($ids_arr){
		$q="select SQL_CALC_FOUND_ROWS * from 
			(select ev.*,
				2 as sort_order
			from evants ev
			where ev.id1 in (".implode(",",$ids_arr).")	
				and (ev.type in (1,2,3,4,5,6,7,8,9,10,11,12,13,14) or (ev.type=15 and id2 is NULL))
			";
		if($my_Obj['id']>0)
			$q.="
			union
			select ev.*, 
				1 as sort_order
			from evants ev
			where ev.id2=".$my_Obj['id']."
				and ev.type in (15, 600, 900, 800, 600, 1500)
			";
		$q.=") t
			order by t.sort_order asc, t.time desc
			limit ".$last.", ".$el_per_page."
			";
		
		//if($_SESSION['valid_user_name']=="Murad") echo $q;	
		$r3=mysql_query($q);
		is_db_error($r3);
		$r4=mysql_query("SELECT FOUND_ROWS()");
		$total_all=mysql_result($r4,0);
		$evants_total=mysql_num_rows($r3);
		$ntfyObj = array("fotos" =>0, "music"=>0, "clips"=>0);
	
		for($j=0;($j<$evants_total);$j++){
		   $evant=mysql_fetch_array($r3);
		   //данные пользователя
			$user_row =  Mob_UserInfo($evant['id1'], "tiny", "");
			$event_ids[]=$evant['id'];
		   $update_evant=how_long_time($evant['time']);
		   $todays_midnight_time=mktime("00", "00", "00", date("n"), date("j"), date("Y"));
			if($evant['sort_order']==1){
				$ntfy=1;
			}	
			else
				$ntfy=0;
			
			if(!(($evant['type']==15)&&($evant['id2']>0)&&($evant['id2']!=$my_Obj['id']))){//какие пропускаем: ответы на комменты не мне
				
			   if($evant['type']==1){//privfoto add
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>array(
							"id"=> $evant['name2'],
							"path"=>$evant['element'],
							"private_foto" =>1),
						"amount"=>$evant['amount'],
						"time"=> $evant['time'],
						"type"=> $evant['type'],
						"source_type"=>"fotos",
						"ntfy" =>$ntfy
						);										

			   }
			   else
			   if($evant['type']==2){//foto   add
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>array(
							"id"=> $evant['element_id'],
							"path"=>$evant['element'],
							"private_foto" =>0),
						"amount"=>$evant['amount'],
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"fotos",
						"ntfy" =>$ntfy
						);
			   }
			   else
			   if($evant['type']==3){//music  add
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>array(
							"id"=> $evant['element_id'],
							"artist"=>iconv("Windows-1251","UTF-8",$evant['element']),
							"path" => $evant['name2']
							),
						"amount"=>$evant['amount'],
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"music",
						"ntfy" =>$ntfy
						);

			   }
			   else
			   if($evant['type']==4){//friends  add				
					if($evant['name1']==$user_row['name'])
						$source= Mob_UserInfo(0, "tiny", $evant['name2']); 
					else
						$source= Mob_UserInfo(0, "tiny", $evant['name1']);
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>$source,
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"user",
						"ntfy" =>$ntfy
						);
										
			   }
			   else
			   if($evant['type']==5){//avatar change
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>$user_row,
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"user",
						"ntfy" =>$ntfy
						);
			   }
			   else
			   if($evant['type']==6 || $evant['type']==600){//vote users_foto
					if($evant['amount']==1) $mark="-";
					else if($evant['amount']==3) $mark="++";
					else $mark="+";
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>array(
							"id"=> $evant['element_id'],
							"path"=>$evant['element'],
							"private_foto" =>1),
						"mark"=>$mark,
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"fotos",
						"ntfy" =>$ntfy
						);
			   }
			   if($evant['type']==7){//add playlist
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>null,
						"amount"=>$evant['amount'],
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>null,
						"ntfy" =>$ntfy
						);
			   }
			   if($evant['type']==8 || $evant['type']==800){//vote foto
					if($evant['amount']==1) $mark="-";
					else if($evant['amount']==3) $mark="++";
					else $mark="+";
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>array(
							"id"=> $evant['element_id'],
							"path"=>$evant['element'],
							"private_foto" =>0),
						"mark"=>$mark,
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"fotos",
						"ntfy" =>$ntfy
						);
			   }
			   if($evant['type']==9 || $evant['type']==900){//vote music
					if($evant['amount']==1) $mark="-";
					else if($evant['amount']==3) $mark="++";
					else $mark="+";
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>array(
							"id"=> $evant['element_id'],
							"artist"=>iconv("Windows-1251","UTF-8",$evant['element']),
							"path" => $evant['name2']
							),
						"mark"=>$mark,
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"music",
						"ntfy" =>$ntfy
						);
			   }
			   if($evant['type']==10 || $evant['type']==1000){//vote video 
					if($evant['amount']==1) $mark="-";
					else if($evant['amount']==3) $mark="++";
					else $mark="+";
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>array(
							"id"=> $evant['element_id'],
							"path"=>$evant['element'].".jpg",
							"title"=>iconv("Windows-1251","UTF-8",$evant['name1'])
							),
						"mark"=>$mark,
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"clips",
						"ntfy" =>$ntfy
						);
			   }
			   else
			   if($evant['type']==13){//video add
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>array(
							"id"=> $evant['amount'],
							"path"=>$evant['element'].".jpg",
							"title"=>iconv("Windows-1251","UTF-8",$evant['name1'])
							),
						"amount" => 0,
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"clips",
						"ntfy" =>$ntfy
						);
			   } 
			   if($evant['type']==14){//gift add  
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>array(
							"path"=>$evant['element']
							),
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"source_type"=>"gift",
						"ntfy" =>$ntfy
						);
			   }
			   if($evant['type']==15 || $evant['type']==1500){//comment add
					if($evant['element']=="fotos"){
						$source = array ("id" => $evant['element_id'], "path" => $evant['name2'], "private_foto" => 0);
						$source_type="fotos";
					}
					else
					if($evant['element']=="users_fotos"){
						$source = array ("id" => $evant['element_id'], "path" => $evant['name2'], "private_foto" => 1);
						$source_type="fotos";
					}
					else
					if($evant['element']=="music"){
						$source = array ("id" => $evant['element_id'], "artist" => iconv("Windows-1251","UTF-8",$evant['name2']), "path" => $evant['path']);
						$source_type="music";
					}
					else
					if($evant['element']=="clips"){
						$source = array ("id" => $evant['element_id'], "path" => $evant['name2'].".jpg");
						$source_type="clips";
					}
					$text = iconv("Windows-1251","UTF-8", $evant['large_text']);
					 //получаем комменты, если ответили мне или под моей фоткой 
					 if(($evant['id2']==$my_Obj['id'])&&($evant['id2']>0)&&($evant['type']==15)){
						$reply=1;						
					 }
					 else{
						$reply=0;
					 }
					$ev_row=array(
						"num" => $evant['num'],
						"user"=>$user_row, 
						"source"=>$source,
						"time"=> $evant['time'],
						"type"=>$evant['type'],
						"text" => $text,
						"reply"=>$reply,
						"source_type"=>$source_type,
						"ntfy" =>$ntfy
						);
					 
									 
				}
				
				$ev[]=$ev_row;
				//объект новых уведомлений
				if($ev_row["ntfy"]==1){
					if($ev_row["source_type"]=="fotos")
						$ntfyObj["fotos"]=$ntfyObj["fotos"]+1;
					if($ev_row["source_type"]=="music")
						$ntfyObj["music"]=$ntfyObj["music"]+1;
					if($ev_row["source_type"]=="clips")
						$ntfyObj["clips"]=$ntfyObj["clips"]+1;
				}
					
			}
		}

		
	}
	return array("total_all" => $total_all, "evants" => $ev, "new_" => $ntfyObj);
}
function mob_user_source_add ($num, $base, $unit_id, $unit_base){
    $num=addslashes($num);
    $base=addslashes($base);
    $unit_id=addslashes($unit_id);
    $unit_base=addslashes($unit_base);
    //МУЗЫКА и ВИДЕО
    if(($base=="music") || ($base=="clips")|| ($base=="smiles") ){
       $q="insert into unit_sources(s_key,s_base,u_key,u_base,time) values(".$num.", '".$base."', ".$unit_id." ,'".$unit_base."',".time().")";
       $r=mysql_query($q);
       is_db_error($r);

       //добавляем обновления для музыки
       if(($base=="music")&&($unit_base=="forum_users")){
           $query="select artist, title from music where num=".$num;
           $result=mysql_query($query);
           $row=mysql_fetch_array($result);
           $query="insert into evants(id1,element, element_id, type,time) values(".$unit_id.",'".$row['artist']."-".$row['title']."',".$num.",3,".time().")";
           $result=mysql_query($query);
           is_db_error($r);
       }
       else
       //добавляем обновления для видео
       if(($base=="clips")&&($unit_base=="forum_users")){
           $query="select path from clips where num=".$num;
           $result=mysql_query($query);
           $row=mysql_fetch_array($result);
           $query="insert into evants(id1,element,type,amount,time) values(".$unit_id.", '".$row['path'].".jpg', 13, ".$num.", ".time().")";
           $result=mysql_query($query);
           is_db_error($r);
       }
       
       return true;
    }
}