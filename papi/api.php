<?php

/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2017年8月2日
 * @link www.xmcms.club
 *  API SDK With PHP
 */
class Api
{

    private static $config;

    private static $server;

    private static $appid;

    private static $secret;

    // 获取API请求结果
    public static function getApi($action, $post = Array())
    {
        self::config();
        $url = self::apiServer() . $action; // 拼接请求地址
        $result = self::curl($url, array_merge($post, self::authData())); // 执行请求
        return $result;
    }

    // 获取API请求解码数据
    public static function getApiDecode($action, $post = Array())
    {
        // 解析json数据
        $result = self::getApi($action, $post);
        if (! $result)
            return;
        $decode = json_decode($result);
        // 如果解析出错，则直接输出获取的数据
        if (json_last_error() == 4) {
            exit($result);
        }
        return $decode;
    }

    // 获取解码数据,带报错检测
    public static function getApiData($action, $post = Array())
    {
        $decode = self::getApiDecode($action, $post);
        if (! $decode)
            return;
        if (! $decode->code) {
            die($decode->data);
        }
        return $decode;
    }

    // 获取配置
    private static function config()
    {
        if (! self::$config) {
            self::$config = include dirname(__FILE__) . '/config.php';
        }
        self::$server = isset(self::$config['server']) ? self::$config['server'] : '';
        self::$appid = isset(self::$config['appid']) ? self::$config['appid'] : '';
        self::$secret = isset(self::$config['secret']) ? self::$config['secret'] : '';
    }

    // 生成服务器地址
    private static function apiServer()
    {
        // 在请求地址上附加接口服务器地址
        if (! $api_server = self::$server) {
            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
            $api_server = $http_type . $_SERVER['HTTP_HOST'] . '/index.php';
        }
        return $api_server;
    }

    // 生成认证信息数据
    private static function authData()
    {
        $data['appid'] = self::$appid; // 认证id
        $data['timestamp'] = time();
        $temp_arr = array(
            self::$appid,
            self::$secret,
            time()
        );
        sort($temp_arr, SORT_STRING); // 排序
        $data['signature'] = md5(md5(implode($temp_arr))); // 签名
        $token = isset($_SESSION['token']) ? $_SESSION['token'] : md5(session_id()); // 用户会话令牌
        $data['token'] = $token;
        return $data;
    }

    // 执行URL请求，并返回数据
    private static function curl($url, $fields = array(), $CA = false)
    {
        $cacert = dirname(__FILE__) . '/cacert.pem'; // CA根证书
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]); // 在HTTP请求中包含一个"User-Agent: "头的字符串。
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); // 在发起连接前等待的时间，如果设置为0，则无限等待
        curl_setopt($ci, CURLOPT_TIMEOUT, 90); // 设置cURL允许执行的最长秒数
        curl_setopt($ci, CURLOPT_URL, $url); // 设置请求地址
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1); // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
                                                     
        // SSL验证
        if ($SSL && $CA) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, true); // 只信任CA颁布的证书
            curl_setopt($ci, CURLOPT_CAINFO, $cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
        } else if ($SSL && ! $CA) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, 0); // 不检查证书中是否设置域名
        }
        
        // 数据字段
        if ($fields) {
            curl_setopt($ci, CURLOPT_POST, true);
            curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($fields));
        }
        
        $output = curl_exec($ci);
        if (curl_errno($ci)) {
            die('执行API请求发生错误，API地址：' . $url . '，错误：' . curl_error($ci));
        }
        curl_close($ci);
        return trim($output, "\xEF\xBB\xBF"); // 去除返回数据的文件Bom信息
    }
}