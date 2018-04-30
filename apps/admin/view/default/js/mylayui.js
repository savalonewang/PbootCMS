layui.use(['element','upload','laydate','form'], function(){
  var element = layui.element;
  var upload = layui.upload;
  var laydate = layui.laydate;
  var form = layui.form;
  
  //获取hash来切换选项卡，假设当前地址的hash为lay-id对应的值
  var layid = location.hash.replace(/^#tab=/, '');
  element.tabChange('tab', layid); //假设当前地址为：http://a.com#test1=222，那么选项卡会自动切换到“发送消息”这一项
  
  //监听Tab切换，以改变地址hash值
  element.on('tab(tab)', function(){
	var clayid=this.getAttribute('lay-id');
	if(clayid){
		location.hash = 'tab='+ this.getAttribute('lay-id');
	}
  });
	
 
  //用户登陆验证
  form.on('submit(login-submit)', function(data){
  	var form = $("#dologin");
    var url = form.attr('action');
    var username = form.find("#username").val();
    var password = form.find("#password").val();
    var checkcode = form.find("#checkcode").val();
    
	$.ajax({
	  type: 'POST',
	  url: url,
	  dataType: 'json',
	  data: {
            username: username,
            password: password,
            checkcode: checkcode
       },
	  success: function (response, status) {
			if (response.code == 1) {
				layer.msg("登入成功！", {icon: 1});
				window.location.href = response.data;
			} else {
				layer.msg("登入失败：" + response.data, {icon: 5});
			} 
      },
      error:function(xhr,status,error){
    	  layer.msg('登入请求发生错误！', {icon: 5});
      }
	});
    return false;
  });
  
  
  var uploadurl = $("#preurl").data('preurl')+'/index/upload';
  
  //执行单文件实例
  var uploadInst = upload.render({
	elem: '.upload' //绑定元素
	,url: uploadurl //上传接口
	,field: 'upload' //字段名称
	,multiple: false //多文件上传
	,accept: 'images' //接收文件类型 images（图片）、file（所有文件）、video（视频）、audio（音频）
	,acceptMime: 'image/*'
	,done: function(res){
	   var item = this.item;
	   var des=$(item).data('des');
	   if(res){
		   $('#'+des).val(res); 
		   layer.msg('上传成功！'); 
	   }else{
		   layer.msg('上传失败！'); 
	   }
	}
	,error: function(){
		layer.msg('上传发生错误！'); 
	}
  });
  
   //执行多文件上传实例
  var files='';
  var uploadsInst = upload.render({
	elem: '.uploads' //绑定元素
	,url: uploadurl //上传接口
	,field: 'upload' //字段名称
	,multiple: true//多文件上传
	,accept: 'images' //接收文件类型 images（图片）、file（所有文件）、video（视频）、audio（音频）
	,acceptMime: 'image/*'
	,done: function(res){
	   if(files){
		   files+=','+res;
	   }else{
		   files+=res;
	   }
	}
  	,allDone: function(obj){
  		var item = this.item;
  	    var des=$(item).data('des');

	    if(files){
	 	   $('#'+des).val(files); 
	 	   layer.msg('成功上传'+obj.successful+'个文件！'); 
	 	   files='';
	    }else{
	 	   layer.msg('全部上传失败！'); 
	    }
	 }
	,error: function(){
		layer.msg('上传发生错误！'); 
	}
  });
  
  //执行附件上传实例
  var uploadFileInst = upload.render({
	elem: '.file' //绑定元素
	,url: uploadurl //上传接口
	,field: 'upload' //字段名称
	,multiple: false //多文件上传
	,accept: 'file' //接收文件类型 images（图片）、file（所有文件）、video（视频）、audio（音频）
	,done: function(res){
	   var item = this.item;
	   var des=$(item).data('des');
	   if(res){
		   $('#'+des).val(res); 
		   layer.msg('上传成功！'); 
	   }else{
		   layer.msg('上传失败！'); 
	   }
	}
	,error: function(){
		layer.msg('上传发生错误！'); 
	}
  });
  
  //使用多日期控件
  useLayDateMultiple('year','year');
  useLayDateMultiple('month','month');
  useLayDateMultiple('time','time');
  useLayDateMultiple('date','date');
  useLayDateMultiple('datetime','datetime');

  //选择模型切换模板
   form.on('select(model)', function(data){
	  var elem = data.elem;
	  var type = $(elem).find("option:selected").data('type');
	  var listtpl = $(elem).find("option:selected").data('listtpl');
	  var contenttpl = $(elem).find("option:selected").data('contenttpl');
	  
	  $("#type").val(type);
	  addOptionValue("listtpl",listtpl,listtpl);
	  addOptionValue("contenttpl",contenttpl,contenttpl);
	  $("#listtpl").val(listtpl);
	  $("#contenttpl").val(contenttpl);
	  form.render(null, 'sort'); 
	}); 
   
});



//日期控件函数
function useLayDateMultiple(cls,type) {
	layui.use('laydate', function() {
		var laydate = layui.laydate;
		lay('.' + cls).each(function() {
			laydate.render({
				elem : this,
				type : type,
			});
		});
	});
} 


//判断option是否存在，如果不存在就增加
function addOptionValue(id,value,text) {  
    if(!isExistOption(id,value)){$('#'+id).append("<option value="+value+">"+text+"</option>");}      
} 

//判断option是否存在
function isExistOption(id,value) {  
    var isExist = false;  
    var count = $('#'+id).find('option').length;  
      for(var i=0;i<count;i++)     
      {     
         if($('#'+id).get(0).options[i].value == value)     
             {     
                   isExist = true;     
                        break;     
                  }     
        }     
        return isExist;  
} 
