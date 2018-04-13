$(document).ready(function (e) {
	//新增数据行
	$(".rows").on("click",".fa-plus-circle",function(){
		var rowname = $(this).attr("data"); //行名class
		var rowobj=$(this).parents("."+rowname);//当前被克隆的行
		
		//克隆行
		$("."+rowname).removeAttr("id");
		rowobj.clone(true).attr("id", "newrow").insertAfter(rowobj);
		
		//清空新行newrow的数据
		$("."+rowname).siblings("#newrow").find("input").val("");
		$("."+rowname).siblings("#newrow").find("select").val("");
		$("."+rowname).siblings("#newrow").find("textarea").val("");
		
		//对所有行日期控件绑定重新生成
		$("."+rowname).find(".datepicker").removeAttr("id");
		$("."+rowname).find(".datepicker").removeClass("hasDatepicker");
		$("."+rowname).find(".datepicker").datepicker();
	})

	//移除数据行
	$(".rows").on("click",".fa-minus-circle",function(){
		var target = $(this).attr("data");
		$(this).parents("."+target).remove();
	})
})