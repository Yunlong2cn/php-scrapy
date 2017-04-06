<?php
namespace yunlong2cn\ps;


class Router
{
    public static function run()
    {
        // 设置默认 controller action params
        $controller = 'index';
        $action = 'index';
        $params = [];

        if('cli' == php_sapi_name()) {
            // 获取执行参数
            $argc = $_SERVER['argc'];
            $argv = $_SERVER['argv'];
            
            $cs = explode('/', $argv[1]);
            
        } else {
            $cs = explode('/', trim($_SERVER['PATH_INFO'], '/'));
        }

        if(!empty($cs[0])) $controller = $cs[0];
        if(!empty($cs[1])) $action = $cs[1];

        

        $controller = 'yunlong2cn\\ps\\demo\\' . $controller;

        if($argc > 2) {// 说明有参数
            $reflector = new \ReflectionClass($controller);
            $parameters = $reflector->getMethod($action)->getParameters();
            $i = 2;
            foreach ($parameters as $param) {
                if($i > count($argv)) break;
                if(empty($argv[$i])) break;

                $params[$param->name] = $argv[$i];
                $i++;
            }
        }

        $ctrl = new $controller;

        call_user_func_array([$ctrl, $action], $params);
    }
}



