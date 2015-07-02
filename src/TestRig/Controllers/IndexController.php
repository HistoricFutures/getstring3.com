<?php

/**
 * @file
 * Controller for the index.
 */

namespace TestRig\Controllers
{
    use Silex\Application;
    use Symfony\Component\HttpFoundation\Request;
    use TestRig\Controllers\BaseController;

    /** 
     * @class
     * Controller to handle the homepage index.
     */
    class IndexController extends BaseController
    {   
        /**
         * Handles GET method.
         */
        public function get(Request $request, Application $app)
        {
            return $this->render($app);
        }
    }   
}
