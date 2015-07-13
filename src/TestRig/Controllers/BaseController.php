<?php

/**
 * @file
 * Base controller, simplifying e.g. render methods.
 */

namespace TestRig\Controllers;

/** 
 * @class
 * Base controller.
 */
class BaseController
{
    // Default template for rendering.
    protected $template = "index.html";

    protected function render($app, $variables = array())
    {
        return $app['twig']->render($this->template, $variables);
    }
}
