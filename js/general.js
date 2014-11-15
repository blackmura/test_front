function rand( min, max ) { // Generate a random integer
    if( max ) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    } else {
        return Math.floor(Math.random() * (min + 1));
    }
}
function timestamp2date(timestamp) { 
    var theDate = new Date(timestamp * 1000); 
    return theDate; 
}
function getUnixTime(){
	return(Math.round(new Date().getTime() / 1000));
}
function how_long_time(date){ 
	var today= new Date();
	var day_label;
	var time_label;
	var date_label;
	var text_label;
	var pos=-1;
	var month_names = new Array("������", "�������", "�����", "������", "���", "����", "����", "�������", "��������", "�������", "������", "�������");
	
	var day_1= new Date();
		day_1.setDate(day_1.getDate()-1);
	var day_2= new Date();
		day_2.setDate(day_2.getDate()-2);
	//�����
	pos=date.toLocaleTimeString().indexOf(":");
	if(pos!=-1){
		pos=date.toLocaleTimeString().indexOf(":", pos+1);
		if(pos!=-1){
			time_label=date.toLocaleTimeString().substr(0,pos);
		}
		else time_label="0:0";
		
	}
	else time_label="0:0";
	//����
	if(today.getFullYear() == date.getFullYear())
		date_label=date.getDate()+" "+(month_names[date.getMonth()+1]); 
	else
		date_label=date.getDate()+" "+(month_names[date.getMonth()+1])+" "+date.getFullYear();
	
	//���� �������
	if(date.getDate() == today.getDate()){
		day_label="�������";
		text_label=time_label;
	}
	else //���� �����
	if(day_1.toLocaleDateString()== date.toLocaleDateString()){
		day_label="�����";
		text_label=day_label+" � "+time_label;
	}
	else //���� ���������
	if(day_2.toLocaleDateString()== date.toLocaleDateString()){
		day_label="���������";
		text_label=day_label+" � "+time_label;
	}
	else //
	{
		day_label=date_label;
		text_label=day_label+" � "+time_label;
	}
	
	return {day : day_label, msg_label: text_label, time : time_label}
}
function conv($1,$2){
		 var text="";
	
         var foto = /\S+\.(jpg|jpeg)$/i;
         var image = /\S+\.(gif|png)$/i;
		 var youtube_match = /http:\/\/(www\.)?youtube\.com\/watch\?(?:.*&)?v=([A-Za-z0-9_\-]+)(&.*)?/i;
		 var smotri_match = /http:\/\/(www\.)?smotri\.com\/video\/view\/\?id=([A-Za-z0-9_\-]+)(&.*)?/i;
		 var rutube_match = /http:\/\/(www\.)?rutube\.ru\/tracks\/[0-9]+\.html\?v=([A-Za-z0-9_\-]+)(&.*)?/i;
		 var videomail_match = /http:\/\/(www\.)?video\.mail\.ru\/(.+)\.html(\?.+)?/i;
         var user = /http:\/\/(www\.)?shax-dag\.ru.*info_name=.*/i;
         var internal_link=/^(http:\/\/(www\.)?shax-dag\.ru)/i;
		 //���������� JPEG
         if(foto.test($2)) return " <div><img src='"+GLOBAL_SERVER+"tools/resize.php?f="+$2+"&w=150' ></div> ";
         //���������� GIF PNG
		 else if(image.test($2)) return " <img src='"+$2+"'> ";
		 // ������ �� �������� ������
         else if(user.test($2)) return " <a href='"+$2+"'>������ �� ������</a> ";
		 //������ �� ����� youtube
		 else if  (youtube_match.test($2)){
			text=$2;
			text=text.replace(youtube_match,youtube_conv);	
			return text;
		 }			 
		 //���������� ������
         else if(internal_link.test($2)){
             var internal_link1=/http:\/\/(www\.)?shax-dag\.ru(\/)+(login\.php)|(logout\.php)|(profile\.php)|(messages\.php)|(friends\.php)|(ajax\/delete_usersfotos\.php)/i;
             if(internal_link1.test($2))
                 return $2;
             else
                 return " <a href='"+$2+"'>������ �� ��������</a> ";
         }
		 //������� ������
         else return " <a href='away.php?url="+encodeURIComponent($2)+"'>"+$2+"</a> ";
}
function source($1,$2,$3,$4){
		
         if($2=="video")
            return "<div t_key='"+$3+"' base='clips' class='richtext comments clips'><img src='http://video.shaxdag.com/shots/"+$4+"'></div>";
         else
         if($2=="music")
            return "<div t_key='"+$3+"' base='music' class='richtext comments music'>"+$4+"</div>";
         else
         if($2=="foto")
            return "<div t_key='"+$3+"' base='fotos' class='richtext comments fotos'><img src='"+GLOBAL_SERVER+"fotos/small/"+$4+"'></div>";
		 else
         if($2=="users_fotos")
            return "<div t_key='"+$3+"' base='users_fotos' class='richtext comments fotos'><img src='"+GLOBAL_SERVER+"users_fotos/small/"+$4+"'></div>";
         else
            return "";
			
		
}
function attachment($1,$2,$3){         
         if($2=="foto")
            return "<div><img src='"+GLOBAL_SERVER+"attachments/fotos/small/"+$3+"' ></div>";
         if($2=="graffiti")
            return "<div><img src='"+GLOBAL_SERVER+"attachments/graffiti/small/"+$3+"'></div>";
        
		 else
            return "";
}
function extsmiles($1,$2){
         return "<img src='"+GLOBAL_SERVER+"images/users_smiles/"+$2+"' border='0'>";
}
function youtube_conv($1,$2,$3){
	var text="";
	/*
	text='<div align="center" style="padding:0px 10px;"><object width="450" height="325">';
	text+='<param name="movie" value="http://www.youtube.com/v/'+$3+'&amp;hl=ru_RU&amp;fs=1?rel=0&amp;color1=0xe1600f&amp;color2=0xfebd01&amp;version=3"></param>';
	text+='<param name="allowFullScreen" value="true"></param>';
	text+='<param name="allowscriptaccess" value="always"></param>';
	text+='<embed src="http://www.youtube.com/v/'+$3+'&amp;hl=ru_RU&amp;fs=1?rel=0&amp;color1=0xe1600f&amp;color2=0xfebd01&amp;version=3" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="450" height="325"></embed>';
	text+='</object></div>';
	*/
	return text;
}
function smotri_conv($1,$2,$3){
	var text="";
	/*
	text+='<div align="center" style="padding:0px 10px;"><object id="smotriComVideoPlayer" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="450" height="325">';
	text+='<param name="movie" value="http://pics.smotri.com/player.swf?file='+$3+'&bufferTime=3&autoStart=false&str_lang=rus&xmlsource=http%3A%2F%2Fpics.smotri.com%2Fcskins%2Fblue%2Fskin_color.xml&xmldatasource=http%3A%2F%2Fpics.smotri.com%2Fskin_ng.xml" />';
	text+='<param name="allowScriptAccess" value="always" /><param name="allowFullScreen" value="true" />';
	text+='<param name="bgcolor" value="#ffffff" />';
	text+='<embed src="http://pics.smotri.com/player.swf?file='+$3+'&bufferTime=3&autoStart=false&str_lang=rus&xmlsource=http%3A%2F%2Fpics.smotri.com%2Fcskins%2Fblue%2Fskin_color.xml&xmldatasource=http%3A%2F%2Fpics.smotri.com%2Fskin_ng.xml" quality="high" allowscriptaccess="always" allowfullscreen="true" wmode="opaque"  width="450" height="325" type="application/x-shockwave-flash"></embed>';
	text+='</object></div>';
	*/
	return text;
}
function rutube_conv($1,$2,$3){
	var text="";
	/*
	text+='<div align="center" style="padding:0px 10px;"><OBJECT width="450" height="325">';
	text+='<PARAM name="movie" value="http://video.rutube.ru/'+$3+'"></PARAM>';
	text+='<PARAM name="wmode" value="window"></PARAM>';
	text+='<PARAM name="allowFullScreen" value="true"></PARAM>';
	text+='<EMBED src="http://video.rutube.ru/'+$3+'" type="application/x-shockwave-flash" wmode="window" width="450" height="325" allowFullScreen="true" ></EMBED>';
	text+='</OBJECT></div>';
	*/
	return text;
}
function videomail_conv($1,$2,$3){
	var text="";
	/*
	text+='<div align="center" style="padding:0px 10px;"><object width="450" height="325">';
	text+='<param name="allowScriptAccess" value="always" />';
	text+='<param name="movie" value="http://img.mail.ru/r/video2/player_v2.swf?movieSrc='+$3+'" />';
	text+='<embed src=http://img.mail.ru/r/video2/player_v2.swf?movieSrc='+$3+' type="application/x-shockwave-flash" width="450" height="325" allowScriptAccess="always"></embed>';
	text+='</object></div>';
	*/
	return text;
}
function makesmile(text){
         var ret;
         var smile=/:D/g;
         text=text.replace(smile,"<img src='images/smiles/1.png' border=0 class='emoticon'>");
         var smile=/\)\)\)/g;
         text=text.replace(smile,"<img src='images/smiles/1.png' border=0 class='emoticon'>");
         smile=/:\)/g;
         text=text.replace(smile,"<img src='images/smiles/2.png' border=0 class='emoticon'>");
         smile=/\)\)/g;
         text=text.replace(smile,"<img src='images/smiles/2.png' border=0 class='emoticon'>");
         smile=/\-\)/g;
         text=text.replace(smile,"<img src='images/smiles/2.png' border=0 class='emoticon'>");
         smile=/:\(/g;
         text=text.replace(smile,"<img src='images/smiles/3.png' border=0 class='emoticon'>");
         smile=/:o/g;
         text=text.replace(smile,"<img src='images/smiles/27.png' border=0 class='emoticon'>");
         smile=/:B/g;
         text=text.replace(smile,"<img src='images/smiles/14.png' border=0 class='emoticon'>");
         smile=/:oops:/g;
         text=text.replace(smile,"<img src='images/smiles/44.png' border=0 class='emoticon'>");
         smile=/:cry:/g;
         text=text.replace(smile,"<img src='images/smiles/12.png' border=0 class='emoticon' >");
         smile=/:roll:/g;
         text=text.replace(smile,"<img src='images/smiles/15.png' border=0 class='emoticon'>");
         smile=/:surp:/g;
         text=text.replace(smile,"<img src='images/smiles/7.png' border=0 class='emoticon'>");
         smile=/;\)/g;
         text=text.replace(smile,"<img src='images/smiles/6.png' border=0 class='emoticon'>");
         smile=/:P/g;
         text=text.replace(smile,"<img src='images/smiles/11.png' border=0 class='emoticon'>");
         smile=/@}\-/g;
         text=text.replace(smile,"<img src='images/smiles/rose.png' border=0 class='emoticon'>");
         smile=/:ir:/g;
         text=text.replace(smile,"<img src='images/smiles/9.png' border=0 class='emoticon'>");
         smile=/:appl:/g;
         text=text.replace(smile,"<img src='images/smiles/31.png' border=0 class='emoticon'>");
         smile=/:adore:/g;
         text=text.replace(smile,"<img src='images/smiles/65.png' border=0 class='emoticon'>");
         smile=/:\[/g;
         text=text.replace(smile,"<img src='images/smiles/ah.png' class='emoticon'>");
         
         smile=/:\-\*/g;
         text=text.replace(smile,"<img src='images/smiles/aj.png' border=0 class='emoticon'>");
         smile=/:\-X/g;
         text=text.replace(smile,"<img src='images/smiles/al.png' border=0 class='emoticon'>");
         smile=/:\-z/g;
         text=text.replace(smile,"<img src='images/smiles/ao.png' border=0 class='emoticon'>");
         smile=/\*JOKINGLY\*/g;
         text=text.replace(smile,"<img src='images/smiles/ap.png' border=0 class='emoticon'>");
         smile=/\[:\-/g;
         text=text.replace(smile,"<img src='images/smiles/ar.png' border=0 class='emoticon'>");
         smile=/\]:\-/g;
         text=text.replace(smile,"<img src='images/smiles/aq.png' border=0 class='emoticon'>");
         smile=/\|m\|/g;
         text=text.replace(smile,"<img src='images/smiles/bd.png' border=0 class='emoticon'>");
         smile=/@=/g;
         text=text.replace(smile,"<img src='images/smiles/bb.png' border=0 class='emoticon'>");
         return text;

}
$.fn.text2richtext = function () {
	return this.each (function (){
		var url_match = /(?:[^"]|^)(https?:\/\/([A-Za-z0-9\-]+\.)+[a-z]{2,4}(:\d+)?((\/|\?)\S*)?)(?:\s|$)/g;
		var source_match = /\[a (video|music|foto|users_fotos)\=([0-9]+)]([^\[]+)\[\/a\]/g;
		var attachment_match_foto = /\[(foto)\]([0-9A-Za-z_\.\/\-]+)\[\/foto\]/g;
		var attachment_match_graffiti = /\[(graffiti)\]([0-9A-Za-z_\.\/\-]+)\[\/graffiti\]/g;
		var extsmiles_match = /\*([0-9]+\.(gif|png|GIF|PNG))\*/g;
		//text=makesmile(text);
		/* ���� �������� ��� ��������� ������
		text=text.replace(url_match,conv);
		text=text.replace(source_match,source);
		text=text.replace(attachment_match_foto,attachment);
		text=text.replace(attachment_match_graffiti,attachment);
		text=text.replace(extsmiles_match,extsmiles);
		*/
		$(this).html(makesmile($(this).html()));
		$(this).html(
				$(this).html().replace(extsmiles_match,extsmiles)
		);
		$(this).html(
				$(this).html().replace(attachment_match_graffiti,attachment)
		);
		$(this).html(
				$(this).html().replace(attachment_match_foto,attachment)
		);
		$(this).html(
				$(this).html().replace(url_match,conv)
		);
		$(this).html(
				$(this).html().replace(source_match,source)
		);
	});
	$(".richtext.comments").on("vclick", Comments.Go);
}
//����������� ������� �� ������
var getMaxHeight = function ($elms) {
  var maxHeight = 0;
  $elms.each(function () {
    var height = $(this).height();
    if (height > maxHeight) {
      maxHeight = height;
    }
  });
  return maxHeight;
};
//$('div').height( getMaxHeight($('div')) );
function put1(txt, txtarea_id){
	var txtarea_dom = $("#"+txtarea_id);
	 txtarea_dom.val(txtarea_dom.val()+txt);
	 $( "#popup_window" ).popup( "close");
	 txtarea_dom.focus();
} 
function show_smiles(){
	var smile="";
	if($(this).attr("txtarea_id")!=null){
		smile+="<a href='javascript: put1(\":)\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/2.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":D\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/1.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":(\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/3.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":o\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/27.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":B\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/14.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":[\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/ah.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":cry:\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/12.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":roll:\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/15.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":surp:\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/7.png' border=0 class='emoticon' ></a> ";
		 smile+="<a href='javascript: put1(\";)\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/6.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":P\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/11.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\"@}-\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/rose.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":ir:\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/9.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":appl:\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/31.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":adore:\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/65.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":-*\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/aj.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\":-X\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/al.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\"*JOKINGLY*\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/ap.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\"[:-\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/ar.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\"]:-\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/aq.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\"@=\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/bb.png' border=0 class='emoticon'></a> ";
		 smile+="<a href='javascript: put1(\"|m|\",\""+$(this).attr("txtarea_id")+"\");'><img src='images/smiles/bd.png' border=0 class='emoticon'></a> ";
		show_popup("smiles", smile, $( ":mobile-pagecontainer" ).pagecontainer( "getActivePage" ).attr("id"));
	}
}
function setCookie (name, value, expires, path, domain, secure) {
         document.cookie = name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
}
function getCookie(name) {
	var cookie = " " + document.cookie;
	var search = " " + name + "=";
	var setStr = null;
	var offset = 0;
	var end = 0;
	if (cookie.length > 0) {
		offset = cookie.indexOf(search);
		if (offset != -1) {
			offset += search.length;
			end = cookie.indexOf(";", offset)
			if (end == -1) {
				end = cookie.length;
			}
			setStr = unescape(cookie.substring(offset, end));
		}
	}
	return(setStr);
}
//���������� ��������� url
function getUrlVars(){
	var vars = [], hash;
	var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	for(var i = 0; i < hashes.length; i++)
	{
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = hash[1];
	}
	return vars;
}
function L(url){ // ��������� � ����� ������ �������� PHPSESSID � PHPUSERID ���� ����
	if(User.I.id!=null){
		var $PHPUSERID_="PHPUSERID="+User.I.id;
	}
	else
		var $PHPUSERID_="";
	if(window.sessionStorage.getItem("sessid")){
		PHPSESSID_="PHPSESSID=" + window.sessionStorage.getItem("sessid");
	}
	else
		PHPSESSID_="";
		
	if(url.indexOf("?")>0)
		return url + "&"+PHPSESSID_+"&"+$PHPUSERID_;
	else
		return url + "?"+PHPSESSID_+"&"+$PHPUSERID_;
}
function LS(url){ //��������� � ������ PHPSESSID � ������ URL � http://shax-dag.ru/m/ ������������ ��� ������� � ��������� �������� �� ajax
	return L(GLOBAL_SERVER+'m/' + url);
}
function LS_wo(url){ //��������� � ������ ������ URL � http://shax-dag.ru/m/ ������������ ��� ������� � ��������� �������� �� ajax ��� PHPSESSID
	return GLOBAL_SERVER+'m/' + url;
}
//���� � �������
function LLocal(url){
	return GLOBAL_SERVER + url;
}
//���������� ����� ��� ���
function ImageExist(url) {
   var img = new Image();
   img.src = url;
   return img.height != 0;
}
//effect
function clickEffect1(jqobj){
    jqobj.velocity({opacity:'0.4'},300);
	jqobj.velocity({opacity:'1'},300);

}
function rotateEffect1(jqobj){
    jqobj.velocity({rotateY : "180deg"});
	jqobj.velocity({rotateY : "0deg"});
}
//effect
function removeEffect1(jqobj){
    jqobj.velocity({opacity:'0.0'},{duration: 400, complete: function(obj){
		jqobj.remove();
	}
	});
}
function NumSklon(iNumber, aEndings) 
{
    var sEnding, i;
    iNumber = iNumber % 100;
    if (iNumber>=11 && iNumber<=19) {
        sEnding=aEndings[2];
    }
    else {
        i = iNumber % 10;
        switch (i)
        {
            case (1): sEnding = aEndings[0]; break;
            case (2):
            case (3):
            case (4): sEnding = aEndings[1]; break;
            default: sEnding = aEndings[2];
        }
    }
    return sEnding;
}
function getFileExt(filename){
	var a = filename.split(".");
	if( a.length === 1 || ( a[0] === "" && a.length === 2 ) ) {
		return "";
	}
	return a.pop().toLowerCase(); 
}
function getCurrentPage(){
	return $( ":mobile-pagecontainer" ).pagecontainer( "getActivePage" ).attr("id");
}