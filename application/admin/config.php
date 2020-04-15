<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
$host = cur_host();
return [
    'template' =>[
        'tpl_cache'          => false, // 是否开启模板编译缓存,设为false则每次都会重新编译
        'tpl_replace_string' => [
            'home_url' => $host.'/static/admin',
            'home_host' => $host.'/admin',

        ],
    ],
    //默认错误跳转对应的模板文件
    'dispatch_error_tmpl' => 'base:dispatch_jump',
    //默认成功跳转对应的模板文件
    'dispatch_success_tmpl' => 'base:dispatch_jump',
];
