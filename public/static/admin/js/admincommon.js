/**
 * Created by Administrator on 2017/12/13 0013.
 */

$(document).ready(function(){    

    /*添加按钮弹框 1*/
    $(".BtnAddClass").click(function () {
        $('.addfenlei').show();
        $('#albums-overlay').show();
    })
    /*添加按钮弹框 2*/

    /*取消 1*/
    $(".cancle").click(function () {
        $(this).parent().parent().parent('.jbox').hide();
        $(this).parent().parent('.jbox').hide();
        $('.addfenlei').hide();
        $('.editfenlei').hide();
        $('#albums-overlay').hide();
        window.location.reload();
    })
    /*取消 2*/

    /*清空输入框 1*/
    $('.j-clearIpt').click(function(){
        $(this).parent().find('input').val('');
    })
    /*清空输入框 2*/


    /*全选 1*/
    $(".check-all").click(function (){
        var chk = $(this).prop("checked");
        $("input[type=checkbox]").prop("checked", chk);
    })
    /*全选 2*/

});


/*上传单图 1*/
function J_uploadImg(obj,type){
    K=KindEditor;
    var editor = K.editor({
        uploadJson : "/Login/User/addImage2/type/"+type,
        fileManagerJson : "/Login/User/uploadImgList_11/type/"+type,
        allowFileManager : true
    });
    var _this = $(obj);
    editor.loadPlugin('image', function () {
        editor.plugin.imageDialog({
            imageUrl: K('#url1').val(),
            clickFn: function (url, title, width, height, border, align) {
                _this.parent().parent().parent().find("img").attr("src", url);
                _this.parent().parent().parent().find("input").attr("value", url);
                editor.hideDialog();
            }
        });
    });
}
/*上传单图 2*/


/*上传多图 1*/
function J_uploadImg_more(obj,type){
	//alert(11);
    K=KindEditor;
    var editor = K.editor({
        uploadJson : "/Login/User/addImage2/type/"+type,
        fileManagerJson : "/Login/User/addImageList/type/"+type,
        allowFileManager : true
    });
    var imgname = $(obj).attr("data-name");
    var viewname = $(obj).attr("data-view");
    editor.loadPlugin('multiimage', function() {
        editor.plugin.multiImageDialog({
            clickFn : function(urlList) {
                var div = K('#'+viewname);
                K.each(urlList, function(i, data) {
                    div.append('<div class="goods_imgs">\n' +
                        '<img src="'+data.url+'" onerror="this.src=\'../../Public/uploadimg/nopic.jpg\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail">\n' +
                        '<input type="hidden" name="'+imgname+'" value="'+data.url+'">\n' +
                        '<em class="close" title="删除这张图片" onclick="deleteMultiImage(this)">×</em>\n' +
                        '</div>');
                    /*拖拽图片排序*/
                    $("#"+viewname).dad({
                        draggable: 'img'
                    });

                });
                editor.hideDialog();
            }
        });
    });
}
/*上传多图 2*/



/*删除图片 1*/
function deleteMultiImage(elm){
    $(elm).parent().remove();
}


/*标签分页 1*/
function tab_show(obj, i) {
    $(obj).addClass('active').siblings().removeClass('active');
    $('#tab'+i).show().siblings().hide();
}
/*标签分页 2*/

/*单个/批量更新状态 1*/
function J_ChangeStatusCom(obj) {
    var post ={};
    post.tab = $(obj).attr("data-tab");
    post.ids = $(obj).attr("data-id");
    post.item = $(obj).attr("data-item");
    post.value = $(obj).attr("data-value");
    console.log(post);
    dialog.showTips("确定要进行操作吗?", "firm", function () {
        $(".loader" ).fadeIn("fast");
        $.ajax({
            url: '/Login/AdminCommon/changeStatusCom',
            type: "post",
            dataType: "json",
            data: post,
        }).done(function (res) {
            $(".loader" ).fadeOut("fast");
            if (res.status == 1) {
                dialog.showTips(res.info, "", 1);
                return false;
            } else {
                dialog.showTips(res.info, "warn");
                return false;
            }
        })
    })
}
/*单个/批量更新状态 2*/