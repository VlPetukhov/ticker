<?php
/**
 * View
 * @class View
 * @namespace app
 */

namespace app;


class View {
    protected $layoutFileName;
    protected $viewFileName;
    protected $content = '';
    public $title;

    /**
     * @param                $viewName
     * @param BaseController $controller
     *
     * @throws \Exception
     */
    public function __construct( $viewName, BaseController $controller ) {

        $viewDir = realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views');

        $layoutPath = $viewDir . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'layout.php';
        $viewPath = $viewDir . DIRECTORY_SEPARATOR . $controller->className() . DIRECTORY_SEPARATOR . $viewName . '.php';

        if ( ! file_exists($layoutPath) ) {
            throw new \Exception('Layout file not found!');
        }

        $this->layoutFileName = $layoutPath;

        if ( ! file_exists($viewPath) ) {
            throw new \Exception('View file "' . $viewName . '" not found!');
        }

        $this->viewFileName = $viewPath;
    }

    /**
     * Rendering
     *
     * @param array $data
     *
     * @return string
     */
    public function render( array $data = [] ) {

        foreach( $data as $key => $value ) {
            $$key = $value;
        }

        ob_start();
        include $this->viewFileName;
        $this->content = ob_get_contents();
        ob_clean();

        ob_start();
        include $this->layoutFileName;
        $this->content = ob_get_contents();
        ob_clean();

        return $this->content;
    }

    /**
     * Rendering without layout
     *
     * @param array $data
     *
     * @return string
     */
    public function renderPartial( array $data = [] ) {

        foreach( $data as $key => $value ) {
            $$key = $value;
        }

        ob_start();
        include $this->viewFileName;
        $this->content = ob_get_contents();
        ob_clean();

        return $this->content;
    }
} 