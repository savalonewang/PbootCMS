$(document).ready(function (e) {

    //菜单高亮显示
	light_nav();

	//提交表单必填项检查
	$(".panel-body :submit").on("click",function(){
		var flag = true;
		 $(".checknone").each(function (index, element) {
			if(!$(element).val()){
				$(element).css("border","1px solid red");
				$(element).focus();
				flag = false;
				return false;
			}
	    });
		return flag;
	})
	$(".checknone").on("focusout",function(){
		if($(this).val()){
			$(this).removeAttr("style");
		}
	})

	//选择全部
    $("#selectall").on("click", function () {
       $("#selectitem input:checkbox").prop("checked", true);
        
    })
    
    //反选
    $("#invselect").on("click", function () {
       $("#selectitem input:checkbox").each(function() {
    	   if($(this).prop("checked")){
				$(this).prop("checked",false);
			}else{
				$(this).prop("checked",true);
			}
       })		
    })
    
    //勾选方式选择全部
    $("#checkall").on("click", function () {
    	if($(this).prop("checked")){
    		$(".checkitem:enabled").prop("checked", true);
    	}else{
    		$(".checkitem").prop("checked", false);
    	}
        
    })
    

})

//对菜单进行高亮显示
function light_nav(){
	//二级菜单标记当前栏目
    var url = $('#url').data('url').toLowerCase();
    var controller = $('#controller').data('controller').toLowerCase();
    var flag = false;
   
    //第一种情况，url完全一致
    $('#nav').find('a').each(function (index, element) {
        var aUrl = $(element).attr('href').toLowerCase();
        if (url==aUrl) {
            $(element).parents(".dropdown").addClass("active");
            flag = true;
        }
		if(flag) return false;
    });

    url = url.replace('.html','');
    //第二种情况，菜单的子页面，如翻页
    if(!flag){
        $('#nav').find('a').each(function (index, element) {
            var aUrl = $(element).attr('href').toLowerCase();
            aUrl = aUrl.replace('.html','');
            if (url.indexOf(aUrl)>-1) {
                $(element).parents(".dropdown").addClass("active");
                flag = true;
            }
            if(flag) return false;
        });
    }
	
	//第三种情况，只匹配到控制器，如增、改、删的操作页面
    if(!flag){
        $('#nav').find('a').each(function (index, element) {
            var aUrl = $(element).attr("href").toLowerCase();
            if (aUrl.indexOf('/'+controller+'/')==0||aUrl.indexOf('.php/'+controller+'/')>-1) {
                $(element).parents(".dropdown").addClass("active");
                flag = true;
            }
            if(flag) return false;
        });
    }
    
}
