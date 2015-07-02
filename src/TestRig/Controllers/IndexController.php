<?php

/**
 * @file
 * Controller for the index.
 */

namespace TestRig\Controllers
{
    use Symfony\Component\HttpFoundation\Request;
    use Silex\Application;

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
            return "Test Rig";
        }
    }   
}
