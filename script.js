/* JavaScript scripts by martin
   Last Update: 2003.09.07
*/

insertLink = function(pop, input){ // リンク作成
 var url = prompt(pop, input);
 var com = document.getElementById('com');
 if(url != null && url != ""){  
   var out = url.match(/[^;/?:@&=+\$,A-Za-z0-9\-_.!~*'()%]/);
  if(out){
   var erorr = "URLアドレスに不適切な文字が入っているみたいです。確認して下さい。\n";
   erorr += (out.index+'文字目の"'+out+'"が怪しいです。');
   insertLink(erorr, url);
  } else {
   url = url.replace(/^(http\:\/\/)/g,'');
   var site = prompt("サイト名を入力して下さい。これがリンクされます。", "");
  }
  if(site != null){
   if(site=='') site = prompt("サイト名を入力して下さい。これがリンクされます。", "");
   if(site==""){ // IE5.5 Debug
    var output = "[link:"+url+"]" + site + "[/link]";
   } else {
    var p = site.indexOf(unescape('%00'));
    if(0 <= p) site = site.substr(0,p);
    var output = "[link:" + url + "]" + site + "[/link]";
   }
   com.value += output;
  }
 }
 com.focus();
}

colorMe = function(){
 var com = document.getElementById('com');
 var msg = "スタイルシートが認識する色を指定して下さい.\n"
     msg+= " 例えば， #0E2C5C　や　red　や  rgb(245,150,120)  形式です.";
 var color = prompt(msg,'');
 if(color.match(/([#0-9A-Za-z]+(\(([0-9]{1,3},?){3}\)){0,1})/)){
  color = "[color:" + color + "]" +"ここの間が反映されます"+ "[/color]"; 
  com.value += color;
 }
 com.focus();
}

function floatingInit(){
 moz = window.sidebar ? 1 : 0;
 _offsetY = 100;
 _scrollTop = eval(document.documentElement.scrollTop);
 _calendar = document.getElementById("calendar");
 if(moz){
  _calendar.style.position = "fixed";
 } else floatingEl();
}
function floatingEl(){
 var el = document.documentElement;
 _top = Math.abs(eval(el.scrollTop)-_scrollTop)*0.15;
 _scrollTop += (eval(el.scrollTop) < _scrollTop) ? -_top : _top;
 _calendar.style.top = _offsetY + _scrollTop;
 engine = setTimeout("floatingEl()",50);
}
fixedEl = function(){
 _calendar.style.top = _scrollTop + _offsetY;
}

 window.onload = floatingInit; 
