<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2017年11月29日
 *  接口模块配置文件
 */
return array(
    
    // 模块开启认证
    'auth_app' => 'api',
    
    // 控制器返回数据输出方式
    'return_data_type' => 'json',
    
    // 认证数据
    'api_auth' => array(
        'auth_switch' => true, // 开启验证总开关
        'auth_time' => true, // 验证时间差
        'auth_ip' => true, // 验证IP
        'auth_level' => true, // 验证接口权限
        
        'auth_user' => array( // 认证用户表
            'admin' => array(
                'permitip' => '127.0.0.1', // 限制接入IP,如为空则自适应同服务器，可以为数组允许一组地址
                'secret' => '123456', // 认证密钥
                'level' => array( // 采用all或功能模块数组 array('/api/Index/login')
                    '/api/Index/login'
                )
            )
        )
    )
);