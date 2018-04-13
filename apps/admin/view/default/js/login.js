$(document).ready(function (e) {
	
    $("#dologin").on("click", 'button',checklogin);//登录检测

})

//登录检测
function checklogin() {
    var form = $("#dologin");
    var url = form.attr('action');
    var username = form.find("#username").val();
    var password = form.find("#password").val();
    var checkcode = form.find("#checkcode").val();
    var nocheckcode = form.find("#nocheckcode").html();
    
    if (username == '') {
        alert("用户名不能为空！");
        return false;
    }
    if (password == '') {
        alert("密码不能为空！");
        return false;
    }
    if (nocheckcode == undefined && checkcode == '') {
        alert("验证码不能为空！");
        return false;
    }
    $.post(url,
        {
            username: username,
            password: password,
            checkcode: checkcode
        },
        function (response, status) {
            if (status == "success") {
            	if(!response.match("^\{(.+:.+,*){1,}\}$")){
            		alert(response);
            	} else{
            		var obj = jQuery.parseJSON(response);
                	if (obj.code == 1) {
                        window.location.href = obj.data;
                    } else if (obj.code == 0) {
                        alert("登录失败：" + obj.data);
                    } else {
                        alert(response);
                    }
            	}
            } else {
                alert("程序错误：" + response);
            }
        }
    )
    return false;
}