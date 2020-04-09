//不能为纯数字验证
        jQuery.validator.addMethod("isFigure",function(value,element,param){
            // 正则不能写错。
            var pattern = /^([0-9]*)+$/;
            return this.optional(element) || (!pattern.test(value));
        },"名称不能为纯数字。");

 // 手机号码验证
        jQuery.validator.addMethod("mobile", function(value, element) {
            var length = value.length;
            var mobile = /^1[3|4|5|7|8]\d{9}$/;
            return this.optional(element) || (length == 11 && mobile.test(value));
        }, "手机号码格式错误");
// 电话号码验证
        jQuery.validator.addMethod("phone", function(value, element) {
            var tel = /^(0[0-9]{2,3}\-)?([2-9][0-9]{6,7})+(\-[0-9]{1,4})?$/;    //var tel = /^(d{3,4}-?)?d{7,9}$/g;
            return this.optional(element) || (tel.test(value));
        }, "电话号码格式错误");
// 邮政编码验证
        jQuery.validator.addMethod("zipCode", function(value, element) {
            var tel = /^[0-9]{6}$/;
            return this.optional(element) || (tel.test(value));
        }, "邮政编码格式错误");
// QQ号码验证
        jQuery.validator.addMethod("qq", function(value, element) {
            var tel = /^[1-9]\d{4,9}$/;
            return this.optional(element) || (tel.test(value));
        }, "qq号码格式错误");
// IP地址验证
        jQuery.validator.addMethod("ip", function(value, element) {
            var ip = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
            return this.optional(element) || (ip.test(value) && (RegExp.$1 < 256 && RegExp.$2 < 256 && RegExp.$3 < 256 && RegExp.$4 < 256));
        }, "Ip地址格式错误");
// 身份证号码验证
        jQuery.validator.addMethod("isIdCardNo", function(value, element) {
            //var idCard = /^(\d{6})()?(\d{4})(\d{2})(\d{2})(\d{3})(\w)$/;
            return this.optional(element) || isIdCardNo(value);
        }, "请输入正确的身份证号码。");
// 字母和数字的验证
        jQuery.validator.addMethod("chrnum", function(value, element) {
            var chrnum = /^([a-zA-Z0-9]+)$/;
            return this.optional(element) || (chrnum.test(value));
        }, "只能输入数字和字母(字符A-Z, a-z, 0-9)");
// 中文的验证
        jQuery.validator.addMethod("chinese", function(value, element) {
            var chinese = /^[\u4e00-\u9fa5]+$/;
            return this.optional(element) || (chinese.test(value));
        }, "只能输入中文");
// 下拉框验证
        $.validator.addMethod("selectNone", function(value, element) {
            return value == "请选择";
        }, "必须选择一项");
// 字节长度验证
        jQuery.validator.addMethod("byteRangeLength", function(value, element, param) {
            var length = value.length;
            for (var i = 0; i < value.length; i++) {
                if (value.charCodeAt(i) > 127) {
                    length++;
                }
            }
            return this.optional(element) || (length >= param[0] && length <= param[1]);
        }, $.validator.format("请确保输入的值在{0}-{1}个字节之间(一个中文字算2个字节)"));
//身份证号码的验证规则
        function isIdCardNo(num){
            //if (isNaN(num)) {alert("输入的不是数字！"); return false;}
            var len = num.length, re;
            if (len == 15)
                re = new RegExp(/^(\d{6})()?(\d{2})(\d{2})(\d{2})(\d{2})(\w)$/);
            else if (len == 18)
                re = new RegExp(/^(\d{6})()?(\d{4})(\d{2})(\d{2})(\d{3})(\w)$/);
            else {
                //alert("输入的数字位数不对。");
                return false;
            }
            var a = num.match(re);
            if (a != null)
            {
                if (len==15)
                {
                    var D = new Date("19"+a[3]+"/"+a[4]+"/"+a[5]);
                    var B = D.getYear()==a[3]&&(D.getMonth()+1)==a[4]&&D.getDate()==a[5];
                }
                else
                {
                    var D = new Date(a[3]+"/"+a[4]+"/"+a[5]);
                    var B = D.getFullYear()==a[3]&&(D.getMonth()+1)==a[4]&&D.getDate()==a[5];
                }
                if (!B) {
                    //alert("输入的身份证号 "+ a[0] +" 里出生日期不对。");
                    return false;
                }
            }
            if(!re.test(num)){
                //alert("身份证最后一位只能是数字和字母。");
                return false;
            }
            return true;
        }

// 验证两次输入值是否不相同
        jQuery.validator.addMethod("notEqualTo", function(value, element,param) {
            return value != $(param).val();
        }, $.validator.format("两次输入不能相同!"));
//验证值不允许与特定值等于
        jQuery.validator.addMethod("notEqual", function(value, element,param) {
            return value != param;
        }, $.validator.format("输入值不允许为{0}!"));
// 验证值必须大于特定值(不能等于)
        jQuery.validator.addMethod("gt", function(value, element, param){
            return value > param;
        }, $.validator.format("输入值必须大于{0}!"));

// 必须以特定字符串开头验证
        jQuery.validator.addMethod("begin", function(value, element, param){
            var begin = new RegExp("^" + param);
            return this.optional(element) || (begin.test(value));
        }, $.validator.format("必须以 {0} 开头!"));

// 字符验证
        jQuery.validator.addMethod("string", function(value, element){
            return this.optional(element) ||/^[u0391-uFFE5w]+$/.test(value);
        }, "不允许包含特殊符号!");

// 字符最小长度验证（一个中文字符长度为2）
        jQuery.validator.addMethod("stringMinLength", function(value,element, param) {
            var length = value.length;
            for ( var i = 0; i < value.length; i++) {
                if (value.charCodeAt(i) > 127) {
                    length++;
                }
            }
            return this.optional(element) || (length >=param);
        }, $.validator.format("长度不能小于{0}!"));
// 字符最大长度验证（一个中文字符长度为2）
        jQuery.validator.addMethod("stringMaxLength", function(value,element, param) {
            var length = value.length;
            for ( var i = 0; i < value.length; i++) {
                if (value.charCodeAt(i) > 127) {
                    length++;
                }
            }
            return this.optional(element) || (length <=param);
        }, $.validator.format("长度不能大于{0}!"));

// 电话号码验证
        jQuery.validator.addMethod("isTel", function(value, element) {
            var tel = /^d{3,4}-?d{7,9}$/;    //电话号码格式010-12345678
            return this.optional(element) || (tel.test(value));
        }, "请正确填写您的电话号码");
// 联系电话(手机/电话皆可)验证
        jQuery.validator.addMethod("isPhone", function(value,element) {
            var length = value.length;
            var mobile = /^(((13[0-9]{1})|(15[0-9]{1}))+d{8})$/;
            var tel = /^d{3,4}-?d{7,9}$/;
            return this.optional(element) || (tel.test(value) || mobile.test(value));
        }, "请正确填写您的联系电话");

// 判断是否包含中英文特殊字符，除英文"-_"字符外
        jQuery.validator.addMethod("isContainsSpecialChar", function(value, element) {
            var reg = RegExp(/[(\ )(\`)(\~)(\!)(\@)(\#)(\$)(\%)(\^)(\&)(\*)(\()(\))(\+)(\=)(\|)(\{)(\})(\')(\:)(\;)(\')(',)(\[)(\])(\.)(\<)(\>)(\/)(\?)(\~)(\！)(\@)(\#)(\￥)(\%)(\…)(\&)(\*)(\（)(\）)(\—)(\+)(\|)(\{)(\})(\【)(\】)(\‘)(\；)(\：)(\”)(\“)(\’)(\。)(\，)(\、)(\？)]+/);
            return this.optional(element) || !reg.test(value);
        }, "含有中英文特殊字符");

// 匹配中文(包括汉字和字符)
        jQuery.validator.addMethod("isChineseChar", function(value, element) {
            return this.optional(element) || /^[\u0391-\uFFE5]+$/.test(value);
        }, "匹配中文(包括汉字和字符) ");

// 匹配english
        jQuery.validator.addMethod("isEnglish", function(value, element) {
            return this.optional(element) || /^[A-Za-z]+$/.test(value);
        }, "匹配english");

// 字符验证，只能包含中文、英文、数字、下划线等字符。
        jQuery.validator.addMethod("stringCheck", function(value, element) {
            return this.optional(element) || /^[a-zA-Z0-9\u4e00-\u9fa5-_]+$/.test(value);
        }, "只能包含中文、英文、数字、下划线等字符");

// 匹配密码，以字母开头，长度在6-12之间，只能包含字符、数字和下划线。
        jQuery.validator.addMethod("isPwd", function(value, element) {
            return this.optional(element) || /^[a-zA-Z]\\w{6,12}$/.test(value);
        }, "以字母开头，长度在6-12之间，只能包含字符、数字和下划线。");

// 只能输入[0-9]数字
        jQuery.validator.addMethod("isDigits", function(value, element) {
            return this.optional(element) || /^\d+$/.test(value);
        }, "只能输入0-9数字");



// 判断整数value是否等于0
        jQuery.validator.addMethod("isIntEqZero", function(value, element) {
            value=parseInt(value);
            return this.optional(element) || value==0;
        }, "整数必须为0");

// 判断整数value是否大于0
        jQuery.validator.addMethod("isIntGtZero", function(value, element) {
            value=parseInt(value);
            return this.optional(element) || value>0;
        }, "整数必须大于0");

// 判断整数value是否大于或等于0
        jQuery.validator.addMethod("isIntGteZero", function(value, element) {
            value=parseInt(value);
            return this.optional(element) || value>=0;
        }, "整数必须大于或等于0");

// 判断整数value是否不等于0
        jQuery.validator.addMethod("isIntNEqZero", function(value, element) {
            value=parseInt(value);
            return this.optional(element) || value!=0;
        }, "整数必须不等于0");

// 判断整数value是否小于0
        jQuery.validator.addMethod("isIntLtZero", function(value, element) {
            value=parseInt(value);
            return this.optional(element) || value<0;
        }, "整数必须小于0");

// 判断整数value是否小于或等于0
        jQuery.validator.addMethod("isIntLteZero", function(value, element) {
            value=parseInt(value);
            return this.optional(element) || value<=0;
        }, "整数必须小于或等于0");

// 判断浮点数value是否等于0
        jQuery.validator.addMethod("isFloatEqZero", function(value, element) {
            value=parseFloat(value);
            return this.optional(element) || value==0;
        }, "浮点数必须为0");

// 判断浮点数value是否大于0
        jQuery.validator.addMethod("isFloatGtZero", function(value, element) {
            value=parseFloat(value);
            return this.optional(element) || value>0;
        }, "浮点数必须大于0");

// 判断浮点数value是否大于或等于0
        jQuery.validator.addMethod("isFloatGteZero", function(value, element) {
            value=parseFloat(value);
            return this.optional(element) || value>=0;
        }, "浮点数必须大于或等于0");

// 判断浮点数value是否不等于0
        jQuery.validator.addMethod("isFloatNEqZero", function(value, element) {
            value=parseFloat(value);
            return this.optional(element) || value!=0;
        }, "浮点数必须不等于0");

// 判断浮点数value是否小于0
        jQuery.validator.addMethod("isFloatLtZero", function(value, element) {
            value=parseFloat(value);
            return this.optional(element) || value<0;
        }, "浮点数必须小于0");

// 判断浮点数value是否小于或等于0
        jQuery.validator.addMethod("isFloatLteZero", function(value, element) {
            value=parseFloat(value);
            return this.optional(element) || value<=0;
        }, "浮点数必须小于或等于0");

// 判断浮点型
        jQuery.validator.addMethod("isFloat", function(value, element) {
            return this.optional(element) || /^[-\+]?\d+(\.\d+)?$/.test(value);
        }, "只能包含数字、小数点等字符");

// 匹配integer
        jQuery.validator.addMethod("isInteger", function(value, element) {
            return this.optional(element) || (/^[-\+]?\d+$/.test(value) && parseInt(value)>=0);
        }, "匹配integer");

//自定义validate验证输入的数字小数点位数不能大于两位
        jQuery.validator.addMethod("minNumber",function(value, element){
            var returnVal = true;
            inputZ=value;
            var ArrMen= inputZ.split(".");    //截取字符串
            if(ArrMen.length==2){
                if(ArrMen[1].length>2){    //判断小数点后面的字符串长度
                    returnVal = false;
                    return false;
                }
            }
            return returnVal;
        },"小数点后最多为两位");         //验证错误信息

//自定义validate验证输入的数字小数点位数不能大于一位
        jQuery.validator.addMethod("oneNumber",function(value, element){
            var returnVal = true;
            inputZ=value;
            var ArrMen= inputZ.split(".");    //截取字符串
            if(ArrMen.length==2){
                if(ArrMen[1].length>1){    //判断小数点后面的字符串长度
                    returnVal = false;
                    return false;
                }
            }
            return returnVal;
        },"小数点后最多为一位");         //验证错误信息

// 自定义判断浮点数value是否小于或等于100
        jQuery.validator.addMethod("isFloatLteHundred", function(value, element) {
            value=parseFloat(value);
            return this.optional(element) || value<=100;
        }, "浮点数必须小于或等于100");

// 自定义判断整数value是否小于等于5
        jQuery.validator.addMethod("isIntGtFive", function(value, element) {
            value=parseInt(value);
            return this.optional(element) || value<=5;
        }, "整数必须小于等于5");