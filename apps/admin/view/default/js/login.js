

//登录检测
function checklogin() {
    var form = $("#dologin");
    var url = form.attr('action');
    var username = form.find("#username").val();
    var password = form.find("#password").val();
    var checkcode = form.find("#checkcode").val();
	
	$.ajax({
	  type: 'POST',
	  url: url,
	  dataType: json,
	  data: {
            username: username,
            password: password,
            checkcode: checkcode
       },
	  success: function (response, status) {
			if (response.code == 1) {
				window.location.href = response.data;
			} else if (response.code == 0) {
				alert("登录失败：" + obj.data);
			} else {
				alert(response);
			}
        },
	 error:function(){
		 
	 }
	  
	});
    return false;
}