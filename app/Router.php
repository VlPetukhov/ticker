<?php
/**
 * Request router
 * @class Router
 * @namespace app
 */

namespace app;


class Router {

    public function route( $path ) {

        if ( empty($path) ) {
            //default controller
            $parts = [];
            $controllerName = 'SiteController';
        } else {
            //selected controller
            $parts = explode('/', $path );
            $controllerName = ucfirst(array_shift($parts)) . 'Controller';
        }
        $controllerName = '\\controllers\\' . $controllerName;

        try {
            $controller = new $controllerName();
        } catch( \Exception $e ) {
            $this->show404();
            exit;
        }

        if ( empty( $parts )) {
            //default action
            if ( ! method_exists($controller, 'actionIndex')) {
                $this->show404();
                exit;
            }

            $controller->actionIndex();
        } else {
            //selected action
            $methodName = 'action' . ucfirst(array_shift($parts));

            if ( ! method_exists($controller, $methodName)) {
                $this->show404();
                exit;
            }

            $controller->$methodName($parts);
        }
    }

    public function show404()
    {
        header("HTTP/1.1 404 Not Found");
        include (realpath(str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/../views/404.php')));
    }
} 