<?
session_start();
include("../../sql_func.php");
include "m_lib/m_lib.php";
sql_connect();
/*�� �������������. ������������ ������ MYOBJ ������ ��������� �������� � ����*/
$ObjSessionResult = mob_restore_session($PHPSESSID, $PHPUSERID);
$valid_user_name=$ObjSessionResult["valid_user_name"];
if($valid_user_name){
	$MyObj = Mob_UserInfo(0, "tiny", $valid_user_name);
	$my_id=$MyObj["id"];
	if($_POST['method']=="savePicture"){
		$MAX_FILE_SIZE=10485760; //10mb
		if($_FILES["userfile"]){
			if((detect_format($_FILES["userfile"]['name'])!="jpg")&&(detect_format($_FILES["userfile"]['name'])!="JPG")&&(detect_format($_FILES["userfile"]['name'])!="jpeg")&&(detect_format($_FILES["userfile"]['name'])!="gif")&&(detect_format($_FILES["userfile"]['name'])!="GIF")&&(detect_format($_FILES["userfile"]['name'])!="png")&&(detect_format($_FILES["userfile"]['name'])!="PNG")){
			   mob_die("�� ���������� ������ �����������");
			}
			if(!$_FILES["userfile"]['size']){
			   mob_die("������ ����");
			}
			if($_FILES["userfile"]['size']>$MAX_FILE_SIZE){
			   mob_die("���� ��������� ���������� ������ 10��");
			}
			if(!is_uploaded_file($_FILES["userfile"]['tmp_name'])){
			    mob_die("������ HTTP POST");
			}
			
			$path="../../previews/fotos"; 
			$add=md5(rand(0,99999999));
			$userfile_name=$my_id."_".$add.".".detect_format($_FILES["userfile"]['name']);
			$upfile=$path."/".$userfile_name;
	
			if(!copy($_FILES["userfile"]['tmp_name'],$upfile)){
			   mob_die("������ ��� ����������� ����� ".$_FILES["userfile"]['tmp_name']);
			}

			//������� ���������

			$new_path=$path."/large_800/"; 
			$resize_success=resize_foto($userfile_name,800,$path, $new_path); 
			$new_path=$path."/small_500/"; 
			$resize_success=resize_foto($userfile_name,500,$path, $new_path);
			$new_path=$path."/small_200/"; 
			$resize_success=resize_foto($userfile_name,200,$path, $new_path);
			$q="insert into previews(type, time, path, user_id) values ('fotos', ".time().", '".$userfile_name."', ".$my_id.")";
			$r=mysql_query($q);
			is_db_error($r);
			$q="select max(num) as num from previews where user_id=".$my_id." and type='fotos'";
			$r=mysql_query($q);
			is_db_error($r);
			$row=mysql_fetch_array($r);
			if(mysql_num_rows($r)==1){
				$method_status = array( "auth_status"=>"success", "method_status"=>"success", "src" => $userfile_name, "num" => $row['num']);
				echo json_encode($method_status);
			}
			else
				mob_die("Preview �� ������");
		}
	}
	else
		mob_die("������ � ������");

}
else{
	echo json_encode( array( "auth_status"=>"fail" ) );
}