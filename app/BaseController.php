<?php
/**
 * Created by PhpStorm.
 * User: Vladimir
 * Date: 09.02.2016
 * Time: 23:46
 */

namespace app;


class BaseController {

    /**
     * Returns class name
     * @return mixed
     */
    public function className()
    {
        $calledClass = explode('\\', get_called_class());
        return (array_pop( $calledClass ));
    }

    /**
     * Renders results to specified view
     * @param $viewName
     * @param array $data
     */
    public function render($viewName, $data = [])
    {
        $view = new View($viewName, $this);
        echo $view->render($data);
    }

    /**
     * Renders results to specified view without layout
     * @param $viewName
     * @param array $data
     */
    public function renderPartial($viewName, $data = [])
    {
        $view = new View($viewName, $this);
        echo $view->renderPartial($data);
    }

    /**
     * Redirects to given url
     * @param string|array $url
     * @throws \Exception
     */
    public function redirect( $url ) {

        if (is_array($url)) {
            $baseUrl = array_shift($url);

            if (false !== strpos($baseUrl, 'http')) {
                $urlStr = $baseUrl;
            } else {
                $urlStr = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $baseUrl;
            }

            if ( !empty($url) ) {
                $urlStr .= '?';

                foreach( $url as $paramName => $paramValue) {
                    //skip wrong parameters
                    if ( empty($paramName) ) {
                        continue;
                    }

                    $urlStr .= urlencode($paramName) . '=' . urlencode($paramValue) . '&';
                }

                $urlStr = rtrim($urlStr, '&');
            }
        } elseif( is_string($url)) {
            $urlStr = trim($url);
        } else {
            throw new \Exception("BaseController::redirect error. Wrong URL type was given");
        }

        if (! headers_sent()) {
            header("Location: " . $urlStr);
        }
    }
} 