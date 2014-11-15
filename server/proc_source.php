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
	if($method=="getSourceInfo"){
		if(!check_integer($_GET['t_key']) || !$_GET['base']){
			mob_die("Íå âåğíî ïåğåäàíû ïàğàìåòğû");
		}
		else{
			if($_GET['base']=="users_fotos" || $_GET['base']=="fotos" || $_GET['base']=="clips"){
				$q="select owner from ".$_GET['base']." where num='".$_GET['t_key']."'";
				$r=mysql_query($q);
				is_db_error($r);
				if(mysql_num_rows($r)==1){
					$row=mysql_fetch_array($r);
					$owner=$row['owner'];
				}
				else
					mob_die("İëåìåíò íå íàéäåí");
			}
			$ObjUser = Mob_UserInfo(0, "tiny", $owner);
			//ïğîâåğêà íà äğóæáó
			$q="select 1 from friends where f_id1=".$ObjUser['id']." and f_wait=0 and f_id2='".$MyObj['id']."' or f_id2='".$ObjUser['id']."' and f_wait=0 and f_id1='".$MyObj['id']."'";
			$r=mysql_query($q);;
			if(mysql_num_rows($r)>0) $is_friend=1;
			else $is_friend=0;
			if($_GET['base']=="users_fotos" || $_GET['base']=="fotos"){
				if($_GET['base']=="users_fotos"){
					$q="
							select 
							uf.num,
							uf.title,
							uf.path,
							uf.rating,
							NULL as total_com,
							1 as private_foto,
							ualb.access as foto_access
							from users_fotos uf
								left outer join users_albums ualb  on uf.album_id=ualb.num  
							where uf.num='".$_GET['t_key']."'";
							
				}
				else
				if($_GET['base']=="fotos"){
					$q="
							select 
							uf.num,
							uf.title,
							uf.path,
							uf.rating,
							uf.total_com,
							0 as private_foto
							from fotos uf
							where uf.num='".$_GET['t_key']."' ";
				}
				
				$r=mysql_query($q); 
				is_db_error($r);
				$pict_total=mysql_num_rows($r);
				if($pict_total>0){
					$pict_row=mysql_fetch_array($r);
					if($pict_row['private_foto']){
						$q="select sum(1) from comments2 where t_key =".$pict_row['num']." and base='users_fotos'";
						$r4=mysql_query($q);
						$pict_row['total_com'] = mysql_result($r4,0); 
						if($my_id==$user_id){
							$lock=0;
						}
						else
						if($is_friends && (!$pict_row['foto_access'] || $pict_row['foto_access']==1 || $pict_row['foto_access']==2)){
							$lock=0;
						}
						else
						if (!$pict_row['foto_access'] || $pict_row['foto_access']==1)
							$lock=0;
						
						else
							$lock=1;
						
					} 
					$ObjSource=array(
						"num" => $pict_row['num'], 
						"title" => iconv("Windows-1251","UTF-8",$pict_row['title']), 
						"path" => $pict_row['path'], 
						"rating" => $pict_row['rating'],
						"comments" => $pict_row['total_com'],
						"private_foto" => $pict_row['private_foto'],
						"lock" => $lock
						);
				}
				else
					mob_die("Ğåñóğñ ".$_GET['t_key']." íå íàéäåí");
			}
			else
			if($_GET['base']=="clips"){
				$q="
					select 
					uf.num,
					uf.artist,
					uf.title,
					uf.path,
					uf.rating,
					uf.total_com,
					from clips uf
					where uf.num='".$_GET['t_key']."' ";
				$r=mysql_query($q);
				is_db_error($r);
				$pict_total=mysql_num_rows($r);
				if($pict_total>0){
					$pict_row=mysql_fetch_array($r);
					$ObjSource=array(
						"num" => $pict_row['num'], 
						"title" => iconv("Windows-1251","UTF-8",$pict_row['artist']).": ".iconv("Windows-1251","UTF-8",$pict_row['title']), 
						"path" => $pict_row['path'], 
						"rating" => $pict_row['rating'],
						"comments" => $pict_row['total_com']
						);
				}
				else
					mob_die("Ğåñóğñ ".$_GET['t_key']." íå íàéäåí");
					
			}
			
			
				
			$RESPONSE=json_encode(array (
				"auth_status"=>"success", "method_status"=>"success",
				"element" => array(
					"user" => $ObjUser,
					"base" => $_GET['base'],
					"source" => $ObjSource
					) 
				));  
			echo $RESPONSE; 
			
		}
	}

}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
}