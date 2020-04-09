/*function setCookie(name,value,days){
    var exp=new Date();
    exp.setTime(exp.getTime() + days*24*60*60*1000);
    var arr=document.cookie.match(new RegExp("(^| )"+name+"=([^;]*)(;|$)"));
    document.cookie=name+"="+escape(value)+";expires="+exp.toGMTString();
}*/
function getCookie(name){
    var arr=document.cookie.match(new RegExp("(^| )"+name+"=([^;]*)(;|$)"));
    if(arr!=null){
        return unescape(arr[2]);
        return null;
    }
}
function delCookie(name){
    var exp=new Date();
    exp.setTime(exp.getTime()-1);
    var cval=getCookie(name);
    if(cval!=null){
        document.cookie=name+"="+cval+";expires="+exp.toGMTString();
    }
}


function setCookie(name, value, liveMinutes) {
    if (liveMinutes == undefined || liveMinutes == null) {
        liveMinutes = 60 * 2;
    }
    if (typeof (liveMinutes) != 'number') {
        liveMinutes = 60 * 2;//默认120分钟
    }
    var minutes = liveMinutes * 60 * 1000;
    var exp = new Date();
    exp.setTime(exp.getTime() + minutes + 8 * 3600 * 1000);
    //path=/表示全站有效，而不是当前页
    document.cookie = name + "=" + value + ";path=/;expires=" + exp.toUTCString();

}