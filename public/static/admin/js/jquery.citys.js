/**
 * jquery.citys.js 1.0
 * http://jquerywidget.com
 */
;(function (factory) {
    if (typeof define === "function" && (define.amd || define.cmd) && !jQuery) {
        // AMD或CMD
        define([ "jquery" ],factory);
    } else if (typeof module === 'object' && module.exports) {
        // Node/CommonJS
        module.exports = function( root, jQuery ) {
            if ( jQuery === undefined ) {
                if ( typeof window !== 'undefined' ) {
                    jQuery = require('jquery');
                } else {
                    jQuery = require('jquery')(root);
                }
            }
            factory(jQuery);
            return jQuery;
        };
    } else {
        //Browser globals
        factory(jQuery);
    }
}(function ($) {
    $.support.cors = true;
    $.fn.citys = function(parameter,getApi) {
        if(typeof parameter == 'function'){ //重载
            getApi = parameter;
            parameter = {};
        }else{
            parameter = parameter || {};
            getApi = getApi||function(){};
        }
        var defaults = {
            dataUrl:'/Public/Stores/js/area.json',//数据库地址
            crossDomain: true,        //是否开启跨域
            dataType:'json',          //数据库类型:'json'或'jsonp'
            provinceField:'province', //省份字段名
            cityField:'city',         //城市字段名
            areaField:'area',         //地区字段名
            valueType:'code',         //下拉框值的类型,code行政区划代码,name地名
            code:0,                   //地区编码
            province:0,               //省份,可以为地区编码或者名称
            city:0,                   //城市,可以为地区编码或者名称
            area:0,                   //地区,可以为地区编码或者名称
            required: true,           //是否必须选一个
            nodata: 'hidden',         //当无数据时的表现形式:'hidden'隐藏,'disabled'禁用,为空不做任何处理
            onChange:function(){}     //地区切换时触发,回调函数传入地区数据
        };
        var options = $.extend({}, defaults, parameter);
        return this.each(function() {
            //对象定义
            var _api = {};
            var $this = $(this);
            var $province = $this.find('select[name="'+options.provinceField+'"]'),
                $city = $this.find('select[name="'+options.cityField+'"]'),
                $area = $this.find('select[name="'+options.areaField+'"]');
            $.ajax({
                url:options.dataUrl,
                type:'GET',
                crossDomain: options.crossDomain,
                dataType:options.dataType,
                jsonpCallback:'jsonp_location',
                success:function(data){
                    var province,city,area,hasCity;
                    if(options.code){   //如果设置地区编码，则忽略单独设置的信息
                        var c = options.code - options.code%1e4;
                        if(data[c]){
                            options.province = c;
                        }
                        c = options.code - (options.code%1e4 ? options.code%1e2 : options.code);
                        if(data[c]){
                            options.city = c;
                        }
                        c = options.code%1e2 ? options.code : 0;
                        if(data[c]){
                            options.area = c;
                        }
                    }
                    var updateData = function(){
                        province = {},city={},area={};
                        hasCity = false;       //判断是非有地级城市
                        for(var code in data){
                            if(!(code%1e4)){     //获取所有的省级行政单位
                                province[code]=data[code];
                                if(options.required&&!options.province){
                                    if(options.city&&!(options.city%1e4)){  //省未填，并判断为直辖市
                                        options.province = options.city;
                                    }else{
                                        options.province = code;
                                    }
                                }else if(isNaN(options.province)&&data[code].indexOf(options.province)>-1){
                                    options.province = code;
                                }
                            }else{
                                var p = code - options.province;
                                if(options.province&&p>0&&p<1e4){    //同省的城市或地区
                                    if(!(code%100)){
                                        hasCity = true;
                                        city[code]=data[code];
                                        if(options.required&&!options.city){
                                            options.city = code;
                                        }else if(isNaN(options.city)&&data[code].indexOf(options.city)>-1){
                                            options.city = code;
                                        }
                                    }else if(p>8000){                 //省直辖县级行政单位
                                        city[code] = data[code];
                                        if(options.required&&!options.city){
                                            options.city = code;
                                        }else if(isNaN(options.city)&&data[code].indexOf(options.city)>-1){
                                            options.city = code;
                                        }
                                    }else if(hasCity){                  //非直辖市
                                        var c = code-options.city;
                                        if(options.city&&c>0&&c<100){     //同个城市的地区
                                            area[code]=data[code];
                                            if(options.required&&!options.area){
                                                options.area = code;
                                            }else if(isNaN(options.area)&&data[code].indexOf(options.area)>-1){
                                                options.area = code;
                                            }
                                        }
                                    }else{
                                        area[code] = data[code];            //直辖市
                                        if(options.required&&!options.area){
                                            options.area = code;
                                        }else if(isNaN(options.area)&&data[code].indexOf(options.area)>-1){
                                            options.area = code;
                                        }
                                    }
                                }
                            }
                        }
                    };
                    var format = {
                        province:function(){
                            $province.empty();
                            if(!options.required){
                                $province.append('<option value="">请选择省</option>');
                            }
                            for(var i in province){
                                $province.append('<option value="'+(options.valueType=='code'?i:province[i])+'" data-code="'+i+'">'+province[i]+'</option>');
                            }
                            if(options.province){
                                var value = options.valueType=='code'?options.province:province[options.province];
                                $province.val(value);
                            }
                            this.city();
                        },
                        city:function(){
                            $city.empty();
                            if(!hasCity){
                                $city.css('display','none');
                                $("#city").hide();
                            }else{
                                $("#city").show();
                                $city.css('display','');
                                if(!options.required){
                                    $city.append('<option value="">请选择市</option>');
                                }
                                if(options.nodata=='disabled'){
                                    $city.prop('disabled',$.isEmptyObject(city));
                                }else if(options.nodata=='hidden'){
                                    $city.css('display',$.isEmptyObject(city)?'none':'');
                                }
                                for(var i in city){
                                    $city.append('<option value="'+(options.valueType=='code'?i:city[i])+'" data-code="'+i+'">'+city[i]+'</option>');
                                }
                                if(options.city){
                                    var value = options.valueType=='code'?options.city:city[options.city];
                                    $city.val(value);
                                }else if(options.area){
                                    var value = options.valueType=='code'?options.area:city[options.area];
                                    $city.val(value);
                                }
                            }
                            this.area();
                        },
                        area:function(){
                            $area.empty();
                            $("#area").css('display',$.isEmptyObject(area)?'none':'');
                            if(options.required){
                                $area.removeAttr("disabled");
                            }
                            if(!options.required){
                                $area.append('<option value="">请选择区</option>');
                            }
                            if(options.nodata=='disabled'){
                                $area.prop('disabled',$.isEmptyObject(area));
                            }else if(options.nodata=='hidden'){
                                $area.css('display',$.isEmptyObject(area)?'none':'');
                            }
                            for(var i in area){
                                $area.append('<option value="'+(options.valueType=='code'?i:area[i])+'" data-code="'+i+'">'+area[i]+'</option>');
                            }
                            if(options.area){
                                var value = options.valueType=='code'?options.area:area[options.area];
                                $area.val(value);
                            }
                        }
                    };
                    //获取当前地理信息
                    _api.getInfo = function(){
                        var status = {
                            direct:!hasCity,
                            province:data[options.province]||'',
                            city:data[options.city]||'',
                            area:data[options.area]||'',
                            code:options.area||options.city||options.province
                        };
                        return status;
                    };
                    //事件绑定
                    $province.on('change',function(){
                        options.province = $(this).find('option:selected').data('code')||0; //选中节点的区划代码
                        options.city = 0;
                        options.area = 0;
                        updateData();
                        format.city();
                        options.onChange(_api.getInfo());
                    });
                    $city.on('change',function(){
                        options.city = $(this).find('option:selected').data('code')||0; //选中节点的区划代码
                        options.area = 0;
                        updateData();
                        format.area();
                        options.onChange(_api.getInfo());
                    });
                    $area.on('change',function(){
                        options.area = $(this).find('option:selected').data('code')||0; //选中节点的区划代码
                        options.onChange(_api.getInfo());
                    });
                    //初始化
                    updateData();
                    format.province();
                    if(options.code){
                        options.onChange(_api.getInfo());
                    }
                    getApi(_api);
                }
            });
        });
    };
    $.fn.citys1 = function(parameter,getApi) {
        if(typeof parameter == 'function'){ //重载
            getApi = parameter;
            parameter = {};
        }else{
            parameter = parameter || {};
            getApi = getApi||function(){};
        }
        var defaults = {
            dataUrl:'/Public/Stores/js/area.json',//数据库地址
            crossDomain: true,        //是否开启跨域
            dataType:'json',          //数据库类型:'json'或'jsonp'
            province1Field:'province1', //省份字段名
            city1Field:'city1',         //城市字段名
            area1Field:'area1',         //地区字段名
            valueType:'code',         //下拉框值的类型,code行政区划代码,name地名
            code:0,                   //地区编码
            province1:0,               //省份,可以为地区编码或者名称
            city1:0,                   //城市,可以为地区编码或者名称
            area1:0,                   //地区,可以为地区编码或者名称
            required: true,           //是否必须选一个
            nodata: 'hidden',         //当无数据时的表现形式:'hidden'隐藏,'disabled'禁用,为空不做任何处理
            onChange:function(){}     //地区切换时触发,回调函数传入地区数据
        };
        var options = $.extend({}, defaults, parameter);
        return this.each(function() {
            //对象定义
            var _api = {};
            var $this = $(this);
            var $province1 = $this.find('select[name="'+options.province1Field+'"]'),
                $city1 = $this.find('select[name="'+options.city1Field+'"]'),
                $area1 = $this.find('select[name="'+options.area1Field+'"]');
            $.ajax({
                url:options.dataUrl,
                type:'GET',
                crossDomain: options.crossDomain,
                dataType:options.dataType,
                jsonpCallback:'jsonp_location',
                success:function(data){
                    var province1,city1,area1,hascity1;
                    if(options.code){   //如果设置地区编码，则忽略单独设置的信息
                        var c = options.code - options.code%1e4;
                        if(data[c]){
                            options.province1 = c;
                        }
                        c = options.code - (options.code%1e4 ? options.code%1e2 : options.code);
                        if(data[c]){
                            options.city1 = c;
                        }
                        c = options.code%1e2 ? options.code : 0;
                        if(data[c]){
                            options.area1 = c;
                        }
                    }
                    var updateData = function(){
                        province1 = {},city1={},area1={};
                        hascity1 = false;       //判断是非有地级城市
                        for(var code in data){
                            if(!(code%1e4)){     //获取所有的省级行政单位
                                province1[code]=data[code];
                                if(options.required&&!options.province1){
                                    if(options.city1&&!(options.city1%1e4)){  //省未填，并判断为直辖市
                                        options.province1 = options.city1;
                                    }else{
                                        options.province1 = code;
                                    }
                                }else if(isNaN(options.province1)&&data[code].indexOf(options.province1)>-1){
                                    options.province1 = code;
                                }
                            }else{
                                var p = code - options.province1;
                                if(options.province1&&p>0&&p<1e4){    //同省的城市或地区
                                    if(!(code%100)){
                                        hascity1 = true;
                                        city1[code]=data[code];
                                        if(options.required&&!options.city1){
                                            options.city1 = code;
                                        }else if(isNaN(options.city1)&&data[code].indexOf(options.city1)>-1){
                                            options.city1 = code;
                                        }
                                    }else if(p>8000){//省直辖县级行政单位
                                        city1[code] = data[code];
                                        if(options.required&&!options.city1){
                                            options.city1 = code;
                                        }else if(isNaN(options.city1)&&data[code].indexOf(options.city1)>-1){
                                            options.city1 = code;
                                        }
                                    }else if(hascity1){//非直辖市
                                        var c = code-options.city1;
                                        if(options.city1&&c>0&&c<100){     //同个城市的地区
                                            area1[code]=data[code];
                                            if(options.required&&!options.area1){
                                                options.area1 = code;
                                            }else if(isNaN(options.area1)&&data[code].indexOf(options.area1)>-1){
                                                options.area1 = code;
                                            }
                                        }
                                    }else{
                                        area1[code] = data[code];            //直辖市
                                        if(options.required&&!options.area1){
                                            options.area1 = code;
                                        }else if(isNaN(options.area1)&&data[code].indexOf(options.area1)>-1){
                                            options.area1 = code;
                                        }
                                    }
                                }
                            }
                        }
                    };
                    var format = {
                        province1:function(){
                            $province1.empty();
                            if(!options.required){
                                $province1.append('<option value="">请选择省</option>');
                            }
                            for(var i in province1){
                                $province1.append('<option value="'+(options.valueType=='code'?i:province1[i])+'" data-code="'+i+'">'+province1[i]+'</option>');
                            }
                            if(options.province1){
                                var value = options.valueType=='code'?options.province1:province1[options.province1];
                                $province1.val(value);
                            }
                            this.city1();
                        },
                        city1:function(){
                            $city1.empty();
                            if(!hascity1){
                                $city1.css('display','none');
                                $("#city1").hide();
                            }else{
                                $("#city1").show();
                                $city1.css('display','');
                                if(!options.required){
                                    $city1.append('<option value="">请选择市</option>');
                                }
                                if(options.nodata=='disabled'){
                                    $city1.prop('disabled',$.isEmptyObject(city1));
                                }else if(options.nodata=='hidden'){
                                    $city1.css('display',$.isEmptyObject(city1)?'none':'');
                                }
                                for(var i in city1){
                                    $city1.append('<option value="'+(options.valueType=='code'?i:city1[i])+'" data-code="'+i+'">'+city1[i]+'</option>');
                                }
                                if(options.city1){
                                    var value = options.valueType=='code'?options.city1:city1[options.city1];
                                    $city1.val(value);
                                }else if(options.area1){
                                    var value = options.valueType=='code'?options.area1:city1[options.area1];
                                    $city1.val(value);
                                }
                            }
                            this.area1();
                        },
                        area1:function(){
                            $area1.empty();
                            $("#area1").css('display',$.isEmptyObject(area1)?'none':'');
                            if(options.required){
                                $area1.removeAttr("disabled");
                            }
                            if(!options.required){
                                $area1.append('<option value="">请选择区</option>');
                            }
                            if(options.nodata=='disabled'){
                                $area1.prop('disabled',$.isEmptyObject(area1));
                            }else if(options.nodata=='hidden'){
                                $area1.css('display',$.isEmptyObject(area1)?'none':'');
                            }
                            for(var i in area1){
                                $area1.append('<option value="'+(options.valueType=='code'?i:area1[i])+'" data-code="'+i+'">'+area1[i]+'</option>');
                            }
                            if(options.area1){
                                var value = options.valueType=='code'?options.area1:area1[options.area1];
                                $area1.val(value);
                            }
                        }
                    };
                    //获取当前地理信息
                    _api.getInfo = function(){
                        var status = {
                            direct:!hascity1,
                            province1:data[options.province1]||'',
                            city1:data[options.city1]||'',
                            area1:data[options.area1]||'',
                            code:options.area1||options.city1||options.province1
                        };
                        return status;
                    };
                    //事件绑定
                    $province1.on('change',function(){
                        options.province1 = $(this).find('option:selected').data('code')||0; //选中节点的区划代码
                        options.city1 = 0;
                        options.area1 = 0;
                        updateData();
                        format.city1();
                        options.onChange(_api.getInfo());
                    });
                    $city1.on('change',function(){
                        options.city1 = $(this).find('option:selected').data('code')||0; //选中节点的区划代码
                        options.area1 = 0;
                        updateData();
                        format.area1();
                        options.onChange(_api.getInfo());
                    });
                    $area1.on('change',function(){
                        options.area1 = $(this).find('option:selected').data('code')||0; //选中节点的区划代码
                        options.onChange(_api.getInfo());
                    });
                    //初始化
                    updateData();
                    format.province1();
                    if(options.code){
                        options.onChange(_api.getInfo());
                    }
                    getApi(_api);
                }
            });
        });
    };
}));
