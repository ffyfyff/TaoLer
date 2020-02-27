/**

 @Name：layuiAdmin 社区系统
 @Author：star1029
 @Site：http://www.layui.com/admin/
 @License：LPPL
    
 */


layui.define(['table', 'form'], function(exports){
  var $ = layui.$
  ,table = layui.table
  ,form = layui.form;

  //帖子管理
  table.render({
    elem: '#LAY-app-forum-list'
    ,url: '/admin/Forum/list' //帖子数据接口
    ,cols: [[
      {type: 'checkbox', fixed: 'left'}
      ,{field: 'id', width: 55, title: 'ID', sort: true}
      ,{field: 'poster', title: '贴主'}
      ,{field: 'avatar', title: '头像', width: 100, templet: '#imgTpl', align: 'center'}
      ,{field: 'content', title: '标题', width: 200,templet: '#title'}
      ,{field: 'posttime', title: '时间', sort: true}
      ,{field: 'top', title: '置顶', templet: '#buttonTpl', minWidth: 80, align: 'center'}
	  ,{field: 'hot', title: '精贴', templet: '#buttonHot', minWidth: 80, align: 'center'}
	  ,{field: 'reply', title: '评论状态', templet: '#buttonReply', minWidth: 80, align: 'center'}
	  ,{field: 'check', title: '审帖', templet: '#buttonCheck', minWidth: 80, align: 'center'}
      ,{title: '操作', width: 150, align: 'center', fixed: 'right', toolbar: '#table-forum-list'}
    ]]
    ,page: true
    ,limit: 15
    ,limits: [10, 15, 20, 25, 30]
    ,text: '对不起，加载出现异常！'
  });
  
  //监听工具条
  table.on('tool(LAY-app-forum-list)', function(obj){
    var data = obj.data;
    if(obj.event === 'del'){
      layer.confirm('确定删除此条帖子？', function(index){
        //obj.del();
		$.ajax({
				type:'post',
				url:"/admin/Forum/listdel",
				data:{id:data.id},
				dataType:'json',
				success:function(data){
					if(data.code == 0){
						layer.msg(data.msg,{
							icon:6,
							time:2000
						},function(){
							location.reload();
						});
					} else {
						layer.open({
							title:'删除失败',
							content:data.msg,
							icon:5,
							adim:6
						})
					}
				}
			});
        layer.close(index);
      });
    } else if(obj.event === 'edit'){
      var tr = $(obj.tr);

      layer.open({
        type: 2
        ,title: '编辑帖子'
        ,content: '/admin/Forum/listform?id='+ data.id
        ,area: ['550px', '400px']
        ,btn: ['确定', '取消']
        ,resize: false
        ,yes: function(index, layero){
          var iframeWindow = window['layui-layer-iframe'+ index]
          ,submitID = 'LAY-app-forum-submit'
          ,submit = layero.find('iframe').contents().find("#layuiadmin-form-list")
          ,poster = submit.find('input[name="poster"]').val()
		  ,content = submit.find('input[name="content"]').val()
		  ,avatar = submit.find('input[name="avatar"]').val();

          //监听提交
          iframeWindow.layui.form.on('submit('+ submitID +')', function(data){
            var field = data.field; //获取提交的字段
            
            //提交 Ajax 成功后，静态更新表格中的数据
            //$.ajax({});
			$.ajax({
				type:"post",
				url:"/admin/Forum/listform",
				data:{"id":data.id,"poster":name,"sort":sort,"ename":ename},
				daType:"json",
				success:function (data){
					if (data.code == 0) {
						layer.msg(data.msg,{
							icon:6,
							time:2000
						}, function(){
							location.reload();
						});
					} else {
						layer.open({
							tiele:'修改失败',
							content:data.msg,
							icon:5,
							anim:6
						});
					}
				}
			});
			
			
            table.reload('LAY-app-forum-list'); //数据刷新
            layer.close(index); //关闭弹层
          });  
          
          submit.trigger('click');
        }
        ,success: function(layero, index){
          
        }
      });
    }
  });

  //评论管理
  table.render({
    elem: '#LAY-app-forumreply-list'
    ,url: '/admin/Forum/replys'
    ,cols: [[
      {type: 'checkbox', fixed: 'left'}
      ,{field: 'id', width: 100, title: 'ID', sort: true}
      ,{field: 'replyer', title: '回帖人'}
      ,{field: 'cardid', title: '回帖ID',templet: '#title'}
      ,{field: 'avatar', title: '头像', width: 100, templet: '#imgTpl'}
      ,{field: 'content', title: '回帖内容', width: 200}
      ,{field: 'replytime', title: '回帖时间', sort: true}
	  ,{field: 'check', title: '审核', templet: '#buttonCheck'}
      ,{title: '操作', width: 150, align: 'center', fixed: 'right', toolbar: '#table-forum-replys'}
    ]]
    ,page: true
    ,limit: 15
    ,limits: [10, 15, 20, 25, 30]
    ,text: '对不起，加载出现异常！'
  });
  
  //监听工具条
  table.on('tool(LAY-app-forumreply-list)', function(obj){
    var data = obj.data;
    if(obj.event === 'del'){
      layer.confirm('确定删除此条评论？', function(index){
        //obj.del();
		$.ajax({
				type:'post',
				url:"/admin/Forum/redel",
				data:{id:data.id},
				dataType:'json',
				success:function(data){
					if(data.code == 0){
						layer.msg(data.msg,{
							icon:6,
							time:2000
						},function(){
							location.reload();
						});
					} else {
						layer.open({
							title:'删除失败',
							content:data.msg,
							icon:5,
							adim:6
						})
					}
				}
			});
        layer.close(index);
      });
    } else if(obj.event === 'edit'){
      var tr = $(obj.tr);

      layer.open({
        type: 2
        ,title: '编辑评论'
        ,content: '/admin/Forum/replysform.html'
        ,area: ['550px', '350px']
        ,btn: ['确定', '取消']
        ,resize: false
        ,yes: function(index, layero){
          //获取iframe元素的值
          var othis = layero.find('iframe').contents().find("#layuiadmin-form-replys");
          var content = othis.find('textarea[name="content"]').val();
          
          //数据更新
          obj.update({
            content: content
          });
          layer.close(index);
        }
        ,success: function(layero, index){
            
        }

      });
    }
  });
  
  exports('forum', {})
});