<?php

/**
 * @file
 * Controller for the index.
 */

namespace TestRig\Controllers
{
    use Silex\Application;
    use Symfony\Component\HttpFoundation\Request;

    /** 
     * @class
     * Controller to handle the homepage index.
     */
    class IndexController
    {   
        /**
         * Handles GET method.
         */
        public function get(Request $request, Application $app)
        {
            return $app['twig']->render("index.html");
        }
    }   
}
