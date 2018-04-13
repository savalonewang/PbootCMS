<?php
/**
 * @copyright (C)2016-2099 Hnaoyun Inc.
 * @license This is not a freeware, use is subject to license terms
 * @author XingMeng
 * @email hnxsh@foxmail.com
 * @date 2017年10月15日
 *  配置信息读取类 
 */
namespace core\basic;

class Config
{

    // 存储配置信息
    protected static $configs;

    // 直接获取配置参数
    public static function get($item = null, $array = false)
    {
        // 自动载入配置文件
        if (! isset(self::$configs)) {
            self::$configs = self::loadConfig();
        }
        // 返回全部配置
        if ($item === null) {
            return self::$configs;
        }
        $items = explode('.', $item);
        if (isset(self::$configs[$items[0]])) {
            $value = self::$configs[$items[0]];
        } else {
            return null;
        }
        $items_len = count($items);
        for ($i = 1; $i < $items_len; $i ++) {
            if (isset($value[$items[$i]])) {
                $value = $value[$items[$i]];
            } else {
                return null;
            }
        }
        // 强制返回数据为数组形式
        if ($array && ! is_array($value)) {
            if ($value) {
                $value = explode(',', $value);
            } else {
                $value = array();
            }
        }
        return $value;
    }

    // 写入配置文件
    public static function set($itemName, array $data)
    {
        if ($data) {
            $path = CONF_PATH . '/' . $itemName . '.cache.php';
            // 合并
            if (! ! $configs = self::get($itemName)) {
                $data = mult_array_merge($configs, $data);
            }
            // 待写入
            $config[$itemName] = $data;
            // 写入
            if (check_file($path, true)) {
                $result = file_put_contents($path, "<?php\nreturn " . var_export($config, true) . ";");
                self::assign($path); // 注入配置
                return $result;
            } else {
                return false;
            }
        }
    }

    // 载入配置文件
    private static function loadConfig()
    {
        // 载入配置惯性文件
        if (file_exists(CORE_PATH . '/convention.php')) {
            $configs = require CORE_PATH . '/convention.php';
        } else {
            die('系统框架文件丢失，惯性配置文件不存在！');
        }
        
        // 载入应用路由文件
        if (file_exists(APP_PATH . '/common/route.php')) {
            $config = require APP_PATH . '/common/route.php';
            $configs = mult_array_merge($configs, $config);
        }
        
        // 载入应用版本文件
        if (file_exists(APP_PATH . '/common/version.php')) {
            $config = require APP_PATH . '/common/version.php';
            $configs = mult_array_merge($configs, $config);
        }
        
        if (function_exists('scandir')) {
            $files = scandir(CONF_PATH);
            for ($i = 0; $i < count($files); $i ++) {
                $dir = CONF_PATH . '/' . $files[$i];
                if (is_file($dir)) {
                    $config = require CONF_PATH . '/' . $files[$i];
                    $configs = mult_array_merge($configs, $config);
                }
            }
        } else { // 如果PHP禁用了scandir函数，则手动加载主要配置文件，避免系统错误
            if (file_exists(CONF_PATH . '/config.php')) {
                $config = require CONF_PATH . '/config.php';
                $configs = mult_array_merge($configs, $config);
            }
            if (file_exists(CONF_PATH . '/database.php')) {
                $config = require CONF_PATH . '/database.php';
                $configs = mult_array_merge($configs, $config);
            }
        }
        return $configs;
    }

    // 配置文件注入
    public static function assign($filePath)
    {
        if (file_exists($filePath)) {
            $assign_config = require $filePath;
            if (! is_array($assign_config))
                return;
        }
        if (self::$configs) {
            $configs = mult_array_merge(self::$configs, $assign_config);
        } else {
            $configs = $assign_config;
        }
        self::$configs = $configs;
    }
}

