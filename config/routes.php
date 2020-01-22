<?php

/**
 * 路由配置
 *
 * 规则里必须定义以下任意一个类型：
 *
 * namespace: 全自动分发，指定命名空间，
 *  根据正则捕获命名分组 ct, ac (没指定命名分组时两者默认值均为 index)来自动分发到相应的控制器和方法
 *
 * controller: 半自动分发，指定控制器，
 *  根据正则捕获命名分组 ac (没指定命名分组时默认值为 index)来自动分发到相应的方法
 *
 * action: 手动分发，同时指定控制器和方法
 */

return [
    // 默认路由组
    'default' => [
        [
            // url: /
            'pattern' => '#^/$#',
            'action' => 'App\Controllers\IndexController@index',
        ],
        [
            // url: /foo, /foo/, /foo/bar, /foo/bar/
            'pattern' => '#^/(?<ct>[a-zA-Z_\d]+)/?(?<ac>[a-zA-Z_\d]+)?/?$#',
            'namespace' => 'App\Controllers\\',
        ],
    ],
    // 后台
    'admin' => [
        [
            // url: /secret, /secret/, /secret/index, /secret/index/
            'pattern' => '#^/secret/?(?<ac>[a-zA-Z_\d]+)?/?$#',
            'controller' => 'App\Controllers\Admin\IndexController',
        ]
    ],
    // 运维与开发
    'ops' => [
        [
            // url: /
            'pattern' => '#^/$#',
            'action' => 'App\Controllers\OPS\IndexController@index',
        ],
        [
            // url: /foo, /foo/, /foo/bar, /foo/bar/
            'pattern' => '#^/(?<ct>[a-zA-Z_\d]+)/?(?<ac>[a-zA-Z_\d]+)?/?$#',
            'namespace' => 'App\Controllers\OPS\\',
        ],
    ],
];
