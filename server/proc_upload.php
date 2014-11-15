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
	if($_GET['method']=="uploadFotos"){
		if(!$_GET['id'] || !check_integer($_GET['album']) || !check_integer($_GET['nation']))
			mob_die("Íå âåğíî ïåğåäàííûå ïàğàìåòğû");
		
		$q="select * from previews where num='".addslashes($_GET['id'])."' and user_id=".$my_id;
		$r=mysql_query($q);
		$row= mysql_fetch_array($r);
		is_db_error($r);
		if(mysql_num_rows($r)!=1)
			mob_die("Ìèíèàòşğà ".$_GET['id']." íå íàéäåíà"); 
		 	
		$q="select 1 from nations where num=".$_GET['nation'];
		$r=mysql_query($q);
		if(!mysql_num_rows($r))
			mob_die("Íå íàéäåíà íàğîäíîñòü ".$_GET['nation']." ");
			
		$cluster_dir=date("Y-m");
		$path="../../fotos/".$cluster_dir; $path_no_cluster="../../fotos";
		$userfile_name=$my_id."_shaxdag_".rand(1,1000).".".detect_format($userfile_name);
		$userfile_name_for_sql=$cluster_dir."/".$userfile_name;
		$upfile=$path."/".$userfile_name;
		//åñëè íåò äèğåêòîğèè
		if(!file_exists($path)){
			mkdir($path);
		}
		if(!copy("../../previews/fotos/".$row['path'],$upfile)){
		   mob_die("Îøèáêà êîïèğîâàíèÿ ìèíèìàòşğû");
		}
		$resize_success=resize_foto($userfile_name,1024,$path,$path);
		$new_path=$path_no_cluster."/small/".$cluster_dir;
		if(!file_exists($new_path)){
			mkdir($new_path); 
		}
		$resize_success=resize_foto($userfile_name,150,$path, $new_path);
		
		$title=html_special_chars(utf2win1251($_GET['title']));
		if($placement_type=="general")
			$mod=0;
		else
			if($placement_type=="prior"){
				$q="select bonus, id from forum_users where name='".$valid_user_name."'";
				$r=mysql_query($q);
				$user_row=mysql_fetch_array($r);
				if($user_row['bonus']<$PRIOR_PRICE)
					mob_die ("Íà Âàøåì áîíóñíîì ñ÷åòå íåäîñòàòî÷íî ñğåäñòâ äëÿ ïîëó÷åíèÿ óñëóãè. Ó Âàñ íà ñ÷åòó ".$user_row['bonus']." áîíóñîâ, íåîáõîäèìî ".$PRIOR_PRICE." áîíóñîâ"); 
				$mod=1;
			} 
		else
			$mod=0;
		
		//äîáàâëÿåì â áàçó
		$query="insert into fotos(owner,title,path,album,`mod`,ip, lang) values"."('".$valid_user_name."','".$title."','".$userfile_name_for_sql."',".$album.",".$mod.",'".$REMOTE_ADDR."', '".addslashes($_GET['nation'])."')";
		$result=mysql_query($query);
		$q="select *  from fotos where owner='".$valid_user_name."' and path='".$userfile_name_for_sql."' order by num desc";
		$r2=mysql_query($q);
		$row=mysql_fetch_array($r2);
		$new_id=$row['num'];
		//âğåìåííî äîáàâëÿåì íàğîäíîñòü - îáùàÿ. Íàäî ïåğåéòè íà åäèíóş ñèñòåìó
		$q="insert into crosstable(key1,key2,base,base2) values (0, ".$new_id.",'fotos','nations')";
        $r3=mysql_query($q);
		//äîáàâëÿåì 
		$q="select id1 from evants where id1='".$my_id."' and type=2";
		$r=mysql_query($q);
		if(mysql_num_rows($r)>0){
							$query="update evants set element='small/".$userfile_name."', element_id='".addslashes($new_id)."', amount=amount+1, time=".time()." where id1=".$my_id." and type=2";
							$result=mysql_query($query);
		}
		else{
		   $query="insert into evants(id1,element, element_id, type,amount,time) values(".$my_id.",'small/".$userfile_name."', '".addslashes($new_id)."', 2,1,".time().")";
		   $result=mysql_query($query);
		}
		is_db_error($result);
		$response = array( "auth_status"=>"success", 
			"method_status" => "success", 
			"foto"=>array(
				"num" => $row['num'],
				"path" => $row['path'],
				"rating" => $row['rating'],
				"comments" => $row['total_com'],
				"title" => iconv("Windows-1251","UTF-8", $row['title']),
				"private_foto" => 0
				)
			);
		echo json_encode($response);
	}
	else
		mob_die("Îøèáêà â ìåòîäå");

}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
}