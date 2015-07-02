<?php

/**
 * @file
 * Controllers to handle data generation CRUDI methods.
 */

namespace TestRig\Controllers
{
    use Symfony\Component\HttpFoundation\Request;
    use Silex\Application;
    use TestRig\Controllers\BaseController;
    use TestRig\Models\Dataset;

    /**
     * @class
     * Controller to handle data generation CR*Di methods.
     */
    class DataController extends BaseController
    {
        // Default template for rendering.
        protected $template = "datasets.html";

        /**
         * Handles GET method: CR*Di:Create.
         */
        public function createForm(Request $request, Application $app)
        {
            return $this->render($app, array("title" => "create new dataset"));
        }

        /**
         * Handles POST method: CR*Di:Create.
         */
        public function createSubmit(Request $request, Application $app)
        {
            return "Create new dataset (submit)";
        }

        /**
         * Handles GET method: CR*Di:Read.
         */
        public function read(Request $request, Application $app)
        {
            return $this->render($app, array("title" => "view dataset"));
        }

        /**
         * Handles GET method: CR*Di:Delete.
         */
        public function deleteForm(Request $request, Application $app)
        {
            return $this->render($app, array("title" => "delete dataset"));
        }

        /**
         * Handles POST method: CR*Di:Delete.
         */
        public function deleteSubmit(Request $request, Application $app)
        {
            return "Delete";
        }

        /**
         * Handles GET method: CR*Di:Index.
         */
        public function index(Request $request, Application $app)
        {
            $this->template = "datasets_listing.html";
            $dataset = new Dataset();
            return $this->render(
                $app,
                array("title" => "datasets", "datasets" => $dataset->index())
            );
        }
    }
}

