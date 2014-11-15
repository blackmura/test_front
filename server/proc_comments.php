<?
session_start();
include "../../sql_func.php";
include "m_lib/m_lib.php";
include "../../libs/users.php";
include "../../libs/msg.php";
require_once "../../Comet/Realplexor.php";
sql_connect();
$ObjSessionResult = mob_restore_session($PHPSESSID, $PHPUSERID);
$valid_user_name=$ObjSessionResult["valid_user_name"];
if($valid_user_name){
	if($base!="music" && $base!="clips" && $base!="fotos" && $base!="users_fotos"){
		$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Не известные параметры"));
		echo json_encode($method_status);
		exit;
	}
	if(!$t_key || !$base ||!check_integer($t_key)){
		$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Не переданы все параметры запроса"));
		echo json_encode($method_status);
		exit;
	}
	$auth_status="success";
	$ObjSender = Mob_UserInfo(0, "tiny", $valid_user_name);
	$my_id=$ObjSender['id'];
	//если постим сообщение или впервые открываем переписку то сохраняем объект ресурса и его владельца
	if(($method=="getComments" && $initial==1) || ($method=="postComment")){ 
		if($base=='fotos' || $base=='clips' || $base=='music' || $base=='users_fotos'){
			$q="select * from ".$base." where num='".addslashes($t_key)."'";
			$r=mysql_query($q);
			is_db_error($r);
			$resource = mysql_fetch_array($r);
			$owner= $resource['owner'];
		}
		$ObjOwner = Mob_UserInfo(0, "tiny", $owner);
		//если ресурс - фотографии то заполняем объект тим типом
		if($base == "fotos" || $base == "users_fotos"){
			if($base=="users_fotos")
				$priv_fotos=1;
			else
				$priv_fotos=0;
			$ObjResource = array(
				"base" =>$base,
				"num"=> $resource['num'],
				"title"=> iconv("Windows-1251","UTF-8",$resource['title']),
				"path" => $resource['path'],
				"rating" => $resource['rating'],
				"private_foto" => $priv_fotos
				
			);
		}
		else
		if($base == "music" ){
			$ObjResource = array(
				"base" =>$base,
				"num"=> $resource['num'],
				"artist"=> iconv("Windows-1251","UTF-8",$resource['artist']),
				"title"=> iconv("Windows-1251","UTF-8",$resource['title']),
				"path" => $resource['path'],
				"rating" => $resource['rating'],
				"lang" => $album,
				"comments" => $resource['total_comm']
				
			);
		}
		else
		if($base == "clips" ){
			$ObjResource = array(
				"base" =>$base,
				"num"=> $resource['num'],
				"artist"=> iconv("Windows-1251","UTF-8",$resource['artist']),
				"title"=> iconv("Windows-1251","UTF-8",$resource['title']),
				"path" => $resource['path'],
				"rating" => $resource['rating'],
				"album" => $resource['album'],
				"yt" => $resource['type'],
				"comments" => $resource['total_comm'],
				
				
			);
		}
	}
	else{
		$ObjResource = null;
		$ObjOwner =null;	
	}
	//-----------------------------Отдаем переписку
	if($method=="getComments"){
		if(!$last) $last=0;
		if(!$el_per_page) $el_per_page=16;
		else
		if($el_per_page>60) $el_per_page=60;
		
			
		if($base=='fotos' || $base=='clips' || $base=='music'){
			$comm_table = "comments";
			$q="select SQL_CALC_FOUND_ROWS * from comments where base='".addslashes($base)."' and t_key='".addslashes($t_key)."'
				 order by num desc limit ".$last.", ".$el_per_page;
		}
		else
		if($base=='users_fotos'){
			$comm_table = "comments2";
			$q="select SQL_CALC_FOUND_ROWS * from comments2 where base='".addslashes($base)."' and t_key='".addslashes($t_key)."'
				 order by num desc limit ".$last.", ".$el_per_page;
		}
		
		$r=mysql_query($q);
		is_db_error($r);
		$r3=mysql_query("SELECT FOUND_ROWS()");
		$total_comments=mysql_result($r3,0);
		$total_com=mysql_num_rows($r);
		
		$comments = Array();
		for($i=0;$i<$total_com;$i++){
			$m=mysql_fetch_array($r);
			$comments[]= array("num" =>$m['num'],  
				"text" => iconv("Windows-1251","UTF-8", $m['msg']), 
				"time" => strtotime($m['date']), 
				"user" => Mob_UserInfo(0, "tiny", $m['name'])
				);
		}
		
		$ObjResource["comments"] = $total_comments;
		
		//удаляем оповещения
		$q="delete from mailing_list  where t_key=".intval($t_key)." and type=1 and owner='".$valid_user_name."'";
		$r=mysql_query($q);
		is_db_error($r);
		
		$RESPONSE= json_encode(array("auth_status" => "success", 
			"method_status" => "success", 
			"owner_user" => $ObjOwner, 
			"comments" => $comments, 
			"resource"=>$ObjResource,
			"total_all" => $total_comments
			));
		echo $RESPONSE;
		
	}
	else if($method=="postComment"){
		$msg_utf=$msg;
		$msg_1251= utf2win1251($msg);
		$num1=$ObjOwner['id'];
		$num2=$ObjSender['id'];
		if(!$msg){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Пустое сообщение"));
			echo json_encode($method_status);
			exit;
		}
		//если пользователь удален
		if($ObjSender['del']>0){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Вы не можете отправлять сообщения, пока Ваша страница удалена."));
			echo json_encode($method_status);
			exit;
		}
		//забаненный пользователь
		if($_GET['shax_ban'])
			$is_ban=1;
		else 
			$is_ban=0;
		if($is_ban){ 
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Вы лишены возможности общения из-за нарушения правил сайта"));
			echo json_encode($method_status);
			exit;
		}
		//левак и спам
		if(strstr($msg,"bakez") || strstr($msg,"bangt.ru")){
			$q="update forum_users set del=2 and foto='' where name='".addslashes($valid_user_name)."'";
			$r=mysql_query($q);
			$q="delete from online_users where name='".addslashes($valid_user_name)."'";
			$r=mysql_query($q);
			exit;
		}
		//матерный фильтр
		if(najaz_filter($msg)){ 
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Возможно, Вы пытаетесь опубликовать нецензурное выражение"));
			echo json_encode($method_status);
			exit;
		}
		//данные пользователей
		$q="select * from forum_users where id='".$ObjOwner['id']."'";
		$r=mysql_query($q);
		is_db_error($r);
		$mail_user=mysql_fetch_array($r);
		//я
		$q="select uf.access_rules from forum_users f left outer join user_info uf on f.id=uf.id where f.name='".$valid_user_name."'";
		$r2=mysql_query($q);
		$user_access_rules=access_rules_Decode(mysql_result($r2,0,"access_rules"));
		
		
		//если в черном списке у пользователя
		$q="select * from blacklist where num1=".$num1." and num2=".$num2;
		$r3=mysql_query($q);
		is_db_error($r3);
		if(mysql_num_rows($r3)){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Владелец ресурса добавил в свой черный список"));
			echo json_encode($method_status);
			exit;
		}
		
		//отправляем сообщение на почту
		if($valid_user_name!=$mail_user['name'] && ($base=="fotos" || $base=="users_fotos")){
			$owner_mail=urlencode($mail_user['name']);
			$title_mail=urlencode($title);
			if($base=='fotos'){
				$url_mail = "http://www.shax-dag.ru/comments.php?t_key=".$t_key."&base=fotos";
				$type=1;
			}
			else
			if($base=='users_fotos'){
				$url_mail = "http://www.shax-dag.ru/comments2.php?t_key=".$t_key."&base=users_fotos";
				$type=4;
			}
			
			$msg2="Здравствуйте, ".$mail_user['name']."!
			К вашей фотографии поступили новые комментарии. Для просмотра скопируйте нижеприведенную ссылку  в адресную строку Вашего браузера:
			".$url_mail."

			Если же Вы не хотите следить за комментариями к своим фотографиям, отключите оповещение на личной странице.
			--------------------------------------
			Шах-Даг портал - Я люблю свою Родину!
			http://www.shax-dag.ru
			Адрес администратора mailto: blackmura@gmail.com
			"
			;
			$msg2=addslashes($msg2);
			$time=time();
			add_in_mail_list($msg2,$mail_user['email'],$time,$mail_user['name'],$t_key,$type);
		}
		$msg=addslashes(html_special_chars($msg_1251));
		$name2=addslashes(html_special_chars($valid_user_name));
		if($base=="users_fotos")
			  $query="insert into comments2 (t_key,name,msg,date,base,ip, reply_to) values(".$t_key.",'".$valid_user_name."','".$msg."','".$date."','users_fotos','".$REMOTE_ADDR."', '".addslashes($reply_to)."')";
		else
			$query="insert into comments (t_key,name,msg,date,base,ip, reply_to) values(".$t_key.",'".$valid_user_name."','".$msg."','".$date."','".$base."','".$REMOTE_ADDR."', '".addslashes(intval($reply_to))."')";
		$result2=mysql_query($query);
		is_db_error($result2);
		//обновляем записи в таблице ресурсов
		if(($base=="music")||($base=="fotos")||($base=="clips")||($base=="books")){
			$query="select num from comments where base='".$base."' and t_key=".$t_key." order by num desc";
			$result2=mysql_query($query);
			is_db_error($r);
			$last_com_num=mysql_result($result2,0,"num");
			if(!$last_com_num)
			  $last_com_num=0;
			$query="update ".$base." set total_com=total_com+1, last_com='".$name2."', last_com_num=".$last_com_num." where num=".$t_key;
			$result2=mysql_query($query);
			is_db_error($r);
		}

		//добавляем evants
		if($base=="fotos"){
			$e_id1=$num2;
			$e_element_id=$t_key;
			$e_amount=1;
			$e_name1=$mail_user['name'];			
			$e_element=$base;			
			$e_name2=$ObjResource['path'];
		}
		else
		if($base=="clips"){
			$e_id1=$num2;
			$e_element_id=$t_key;
			$e_amount=1;
			$e_name1=$mail_user['name'];			
			$e_element=$base;			
			$e_name2=$ObjResource['path'];
		}
		else
		if($base=="music"){
			$e_id1=$num2;
			$e_element_id=$t_key;
			$e_amount=1;
			$e_name1=$mail_user['name'];			
			$e_element=$base;			
			$e_name2=$ObjResource['artist']." - ".$ObjResource['title'];
			$e_path = $ObjResource['path'];
		}
		else
		if($base=="books"){
			$e_id1=$num2;
			$e_element_id=$t_key;
			$e_amount=1;
			$e_name1=$mail_user['name'];			
			$e_element=$base;			
			$e_name2=$ObjResource['artist']." - ".$ObjResource['title'];
		}
		else
		if($base=="users_fotos"){
			$e_id1=$num2;
			$e_element_id=$t_key;
			$e_amount=1;
			$e_name1=$mail_user['name'];			
			$e_element=$base;			
			$e_name2=$ObjResource['path'];
		}
		//проверяем есть, ли уже записи по этому ресурсу
		$q="select 1 from evants where type=15 and element='".$e_element."' and element_id='".$e_element_id."' and id1=".$e_id1;
		$r=mysql_query($q);
		is_db_error($r);
		if(mysql_num_rows($r)>0){
			$q="update evants set amount=amount+1, time=".time().", large_text='".$msg."' where type=15 and element='".$e_element."' and element_id='".$e_element_id."' and id1=".$e_id1;
			$r=mysql_query($q);
			is_db_error($r);
		}
		else{
			$q="insert into evants(id1,element,type,amount,time,element_id, name1, name2, large_text, path) values(".$e_id1.",'".$e_element."',15,".$e_amount.",".time().",".$e_element_id.", '".$e_name1."', '".$e_name2."', '".$msg."', '".addslashes($e_path)."')";
			$r=mysql_query($q);
			is_db_error($r);
		}
		//добавляем оповещение по новой методике, если конечно это не я пишу под своей фоткой.
		if(($e_id1!=$num1) && ($reply_to!=$num1)){ 
			$q="select 1 from evants where type=1500 and element='".$e_element."' and element_id='".$e_element_id."' and id1=".$e_id1." and id2=".$num1;
			$r=mysql_query($q);
			is_db_error($r);
			if(mysql_num_rows($r)>0){
				$q="update evants set amount=amount+1, time=".time().", large_text='".$msg."' where type=1500 and element='".$e_element."' and element_id='".$e_element_id."' and id1=".$e_id1." and id2=".$num1;
				$r=mysql_query($q);
				is_db_error($r);
			}
			else{
				 $q="insert into evants(id1,id2,element,type,amount,time,element_id, name1, name2, large_text, path) values(".$e_id1.", '".$num1."', '".$e_element."',1500,".$e_amount.",".time().",".$e_element_id.", '".$e_name1."', '".$e_name2."', '".$msg."', '".addslashes($e_path)."')";
				 $r=mysql_query($q);
				 is_db_error($r);
			}
		}
		//если ответ, то дублируем с айдишником
		if(($reply_to>0)&&check_integer($reply_to)&&($reply_to!=$num2)){
			$q="select 1 from evants where type=15 and element='".$e_element."' and element_id='".$e_element_id."' and id1=".$e_id1." and id2='".addslashes($reply_to)."'";
			$r=mysql_query($q);
			is_db_error($r);
			if(mysql_num_rows($r)>0){
				$q="update evants set amount=amount+1, time=".time().", large_text='".$msg."' where type=15 and element='".$e_element."' and element_id='".$e_element_id."' and id1=".$e_id1." and id2='".addslashes($reply_to)."'";
				$r=mysql_query($q);
				is_db_error($r);
			}
			else{
				 $q="insert into evants(id1, id2, element,type,amount,time,element_id, name1, name2, large_text, path) values(".$e_id1.", '".$reply_to."', '".$e_element."',15,".$e_amount.",".time().",".$e_element_id.", '".$e_name1."', '".$e_name2."', '".$msg."', '".addslashes($e_path)."')";
				 $r=mysql_query($q);
				 is_db_error($r);
			}
		}
		//отправляем в рилплексор 
		$rpl = new Dklab_Realplexor(
			"127.0.0.1", // host at which Realplexor listens for incoming data
			"6969",     // incoming port (see IN_ADDR in dklab_realplexor.conf)
			"users"        // namespace to use (optional)
		);
		if($base=="users_fotos")
			$url_rpl= "comments.php?base=".$base."&t_key=".$t_key;
		else
			$url_rpl= "comments2.php?base=".$base."&t_key=".$t_key;
		if(($num1!=$num2)&&($base=="fotos"))
			$rpl->send("u".$num1, array("url"=>$url_rpl,"type"=>"fotos", "from" => array("name"=>iconv('windows-1251', 'UTF-8',$valid_user_name), "foto"=>$ObjSender['foto'], "id"=>$num2, "gender"=>$ObjSender['gender']), "msg"=>array("timestamp"=>time(), "text"=>html_special_chars($msg_utf))));
		if(($num1!=$reply_to)&&($reply_to>0))
			$rpl->send("u".$reply_to, array("type"=>$base."_reply","url"=>$url_rpl, "from" => array("name"=>iconv('windows-1251', 'UTF-8',$valid_user_name), "foto"=>$ObjSender['foto'], "id"=>$num2, "gender"=>$ObjSender['gender']), "msg"=>array("timestamp"=>time(), "text"=>html_special_chars($msg_utf))));
	
		//отправляем в канал evants
		if(($base=="fotos")||($base=="clips")||($base=="music")){
			if($base=="fotos" || $base=="users_fotos"){
				$rpl->send("site_evants",  array("access_rule"=>$user_access_rules['rules_evants'], "from" => $ObjSender, "data"=>array("timestamp"=>time(), "type"=>"comment_".$base, "params"=>array("text"=>$msg_utf, "path"=>$ObjResource['path'], "owner"=>iconv('windows-1251', 'UTF-8',$ObjOwner['name']), "t_key"=>$t_key))));
			}
			else
			if($base=="music"){
				$rpl->send("site_evants",  array("access_rule"=>$user_access_rules['rules_evants'], "from" => $ObjSender, "data"=>array("timestamp"=>time(), "type"=>"comment_".$base, "params"=>array("text"=>$msg_utf, "artist_title"=>iconv('windows-1251', 'UTF-8',$source['artist']." - ".$source['title']), "t_key"=>$t_key))));
			}
			else
			if($base=="clips"){
				$rpl->send("site_evants",  array("access_rule"=>$user_access_rules['rules_evants'], "from" => $ObjSender, "data"=>array("timestamp"=>time(), "type"=>"comment_".$base, "params"=>array("text"=>$msg_utf, "artist_title"=>iconv('windows-1251', 'UTF-8',$source['artist']." - ".$source['title']), "path"=>$source['path'],  "t_key"=>$t_key))));
			}
			
		}
		//выводим результат
		if($base=='fotos' || $base=='clips' || $base=='music'){
			$q="select * from comments where base='".addslashes($base)."' and t_key='".addslashes($t_key)."'
				 order by num desc limit 0,1";
		}
		else
		if($base=='users_fotos'){
			$q="select * from comments2 where base='".addslashes($base)."' and t_key='".addslashes($t_key)."'
				 order by num desc limit 0,1 ";
		}
		
		$r=mysql_query($q);
		is_db_error($r);
		$total_com=mysql_num_rows($r);
		
		$m=mysql_fetch_array($r);
		$comments= array("num" =>$m['num'],  
			"text" => iconv("Windows-1251","UTF-8", $m['msg']), 
			"time" => strtotime($m['date']), 
			"user" => $ObjSender
			);
		
		
		$RESPONSE= json_encode(array("auth_status" => "success", 
			"method_status" => "success", 
			"comment" => $comments, 
			));
		echo $RESPONSE;
	}
}
else
	echo json_encode( array( "auth_status"=>"fail" ) );
