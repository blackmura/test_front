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
	$auth_status="success";
	$ObjSender = Mob_UserInfo(0, "tiny", $valid_user_name);
	$my_id=$ObjSender['id'];
	//-----------------------------Отдаем переписку
	if($method=="getMessages"){
		if(!$retrievers_id){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Не передан параметр retrievers_id"));
			echo json_encode($method_status);
			exit;
		}
		else{
			if(!$last) $last=0;
			if(!$el_per_page) $el_per_page=16;
			else
			if($el_per_page>60) $el_per_page=60;
			$q="select SQL_CALC_FOUND_ROWS t.*, m.msg
			from(
			select * from msg_out where senders_id='".addslashes($ObjSender['id'])."' and retrievers_id='".addslashes($retrievers_id)."' and del<>1
			union
			select * from msg_in where  senders_id='".addslashes($retrievers_id)."' and retrievers_id='".addslashes($ObjSender['id'])."' and del<>1
			) t,
			msg_text m
			where m.num=t.num
			order by num desc limit ".$last.", ".$el_per_page;
			$r=mysql_query($q);
			is_db_error($r);
			$r3=mysql_query("SELECT FOUND_ROWS()");
			$total_messages=mysql_result($r3,0);
			$total_mes=mysql_num_rows($r);
			
			$ObjRetriever = Mob_UserInfo($retrievers_id, "tiny", "");
			$msg = Array();
			for($i=0;$i<$total_mes;$i++){
				$m=mysql_fetch_array($r);
				$msg[]= array("num" =>$m['num'], "senders_id" => $m['senders_id'], "retrievers_id" => $m['retrievers_id'], "text" => iconv("Windows-1251","UTF-8", $m['msg']), "time" => $m['time']);
			}
			
			//удаляем оповещения
			$q="delete from mailing_list  where t_key=".intval($retrievers_id)." and type=2 and owner='".$valid_user_name."'";
			$r=mysql_query($q);
			is_db_error($r);
			
			$RESPONSE= json_encode(array("auth_status" => $auth_status, 
				"method_status" => "success", 
				"i_user" => $ObjSender, 
				"retriever_user" => $ObjRetriever, 
				"messages"=>$msg,
				"total_all" => $total_messages
				));
			echo $RESPONSE;
			
		}
	}
	else if($method=="postMessage"){
	//-----------------------------------------отправляем сообщения
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
		if(strstr($msg,"bakez") || strstr($msg,"bangt.ru")){
			$q="update forum_users set del=2 and foto='' where name='".addslashes($valid_user_name)."'";
			$r=mysql_query($q);
			$q="delete from online_users where name='".addslashes($valid_user_name)."'";
			$r=mysql_query($q);
			exit;
		}
		if($msg&&$retrievers_id){
			$msg_1251= utf2win1251($msg);  
			$q="select f.*, uf.access_rules, o.name as online_name
			from forum_users f left join user_info uf on uf.id=f.id 
				left outer join online_users o on o.name=f.name
			where f.id='".addslashes($retrievers_id)."'";
			$r=mysql_query($q);
			is_db_error($r);
			$mail_user=mysql_fetch_array($r);
			$user_access_rules=access_rules_Decode($mail_user['access_rules']);
			$num1=$mail_user['id'];
			//черный список
			$q="select * from blacklist where num1=".$num1." and num2=".$my_id;
			$r3=mysql_query($q);
			is_db_error($r3);
			if(mysql_num_rows($r3)){
				$err_txt = $mail_user['name']." ";
				if($mail_user['website']=="Ж") $err_txt.="добавила Вас в свой черный список";
				else
					$err_txt.="добавил Вас в свой черный список";
				$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", $err_txt));
				echo json_encode($method_status);
				exit;
			} 
			//access_rules
			if(!access_rules_check($user_access_rules['rules_msg'],is_friends($num1,$my_id))){
				$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", $valid_user_name." ".$my_id." ".$num1."Only friends can send messages to ".$mail_user['name']));
				echo json_encode($method_status);
				exit;
			}


			$msg_mail=iconv("Windows-1251","UTF-8", "Здравствуйте, ".$mail_user['name']."!
			Вам пришло личное сообщение от ".$valid_user_name.".Для просмотра скопируйте нижеприведенную ссылку  в адресную строку Вашего браузера:
			http://www.shax-dag.ru/messages.php?opt=inbox

			--------------------------------------
			Шах-Даг портал - Я люблю свою Родину!
			http://www.shax-dag.ru
			")
			;
			$msg_mail=addslashes($msg_mail);
			$time=time();
			$type=2;
			add_in_mail_list($msg_mail,$mail_user['email'],$time,$mail_user['name'],$my_id,$type);
			
			$msg_1251=addslashes($msg_1251);
			$time=time();
			$date=date("j.n.Y в H:i");
			//------- NEW MSG
			$q="insert into msg_text(msg) values ('".$msg_1251."')";
			$r=mysql_query($q);
			is_db_error($r);
			$q="select max(num) as maxnum from msg_text ";
			$r=mysql_query($q);
			$last_msgnum=mysql_result($r,0,"maxnum");
			$q="insert into msg_in(num, senders_id, retrievers_id,time,date) values (".$last_msgnum.", ".$my_id.",".$retrievers_id.",".$time.",'".$date."')";
			$r=mysql_query($q);
			is_db_error($r);
			$q="insert into msg_out(num, senders_id, retrievers_id,time,date) values (".$last_msgnum.", ".$my_id.",".$retrievers_id.",".$time.",'".$date."')";
			$r=mysql_query($q);
			is_db_error($r);
			//---------
			if($mail_user['fio'])
				$retriever_fio=$mail_user['fio']." (".$mail_user['name'].")";
			else
				$retriever_fio=$mail_user['name'];
			if($mail_user['online_name'])
				$retriever_status=1;
			else
				$retriever_status=0;
			if(!$mail_user['foto'])$retriever_foto="no_avatar.png";
				else $retriever_foto=$mail_user['foto'];
			$ObjRetriever = array("id" => (string)$mail_user['id'],"name" => iconv("Windows-1251","UTF-8", $retriever_fio), "foto" => $retriever_foto, "status" => $retriever_status); 
			$ObjMsg= array("num" =>$last_msgnum, "senders_id" => $my_id, "retrievers_id" => $mail_user['id'], "text" => htmlspecialchars(stripslashes($msg)), "time" => time());
			$RESPONSE= json_encode(array("auth_status" => $auth_status, "method_status" => "success", "sender_user" => $ObjSender, "retriever_user" => $ObjRetriever, "message"=>$ObjMsg));
			echo $RESPONSE;  
			//send data to RPL server	

			$rpl = new Dklab_Realplexor(
			"127.0.0.1", // host at which Realplexor listens for incoming data
			"6969",     // incoming port (see IN_ADDR in dklab_realplexor.conf)
			"users"        // namespace to use (optional)
			);

			//$rpl->send(array($retrievers_id=>$last_msgnum), "aaaaaa");
			$rpl->send("u".$retrievers_id, 
				array("type"=>"msg", 
					"from" => array(
						"name"=>iconv('windows-1251', 'UTF-8',$valid_user_name), 
						"foto"=>$user_foto, "id"=>$my_id, 
						"gender"=>iconv('windows-1251', 'UTF-8',$user_gender)
						), 
					"msg"=>array(
						"num"=>$last_msgnum, 
						"timestamp"=>time(), 
						"date"=>$date, 
						"text"=>htmlspecialchars(stripslashes($msg))
					), 
					"sender_user" => $ObjSender, 
					"retriever_user" => $ObjRetriever, 
					"message"=>$ObjMsg
					)
				);

		}
		else{
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Не все параметры указаны в запросе"));
			echo json_encode($method_status);
			exit;
		}
	}
	else 
	if($method=="clearDialogNtfy"){
		if(!$retrievers_id){
			$method_status= array ("auth_status" => "success", "method_status" => "fail", "error_text" => iconv("Windows-1251","UTF-8", "Не передан параметр retrievers_id"));
			echo json_encode($method_status);
			exit;
		}
		//удаляем оповещения
		$q="delete from mailing_list  where t_key=".intval($retrievers_id)." and type=2 and owner='".$valid_user_name."'";
		$r=mysql_query($q);
		is_db_error($r);
		echo json_encode( array( "auth_status"=>"success","method_status" => "success") );
	} 
}
else
	echo json_encode( array( "auth_status"=>"fail" ) );
