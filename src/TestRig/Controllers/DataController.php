<?php

/**
 * @file
 * Controllers to handle data generation CRUDI methods.
 */

namespace TestRig\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use TestRig\Controllers\BaseController;
use TestRig\Exceptions\MissingDatasetFileException;
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
     * Implements ::__construct().
     *
     * Set up the Dataset model.
     */
    public function __construct()
    {
        $this->model = new Dataset();
    }

    /**
     * Handles GET/POST method: CR*Di:Create.
     */
    public function create(Request $request, Application $app)
    {
        // Create a form with just an upload widget.
        $form = $app['form.factory']->createBuilder('form')
            ->add('attachment', 'file', array("label" => "Choose a BOP", "required" => true))
            ->getForm();
        $form->handleRequest($request);

        // If form is submitted and (hence) valid, handle file.
        if ($form->isValid()) {
            // Pass file to model layer.
            $path = $this->model->create($form['attachment']->getData());
            return $app->redirect("/data/$path");
        }

        $this->template = "datasets_create.html";
        return $this->render(
            $app,
            array("title" => "create new dataset", "form" => $form->createView())
        );
    }

    /**
     * Handles GET method: CR*Di:Read.
     */
    public function read(Request $request, Application $app)
    {
        // Initialize incoming data.
        $this->template = "datasets_single.html";
        $path = $request->get("path");

        // Get metadata for dataset and inject variables for Twig.
        try {
            $metadata = $this->model->read($path);
            $metadata["more_info"] = $this->model->readRawData($path);
        } catch (MissingDatasetFileException $e) {
            $metadata = array();
        }
        $metadata["title"] = "view dataset";
        $metadata["path"] = $path;


        return $this->render($app, $metadata);
    }

    /**
     * Handles GET/POST method: CR*Di:Delete.
     */
    public function delete(Request $request, Application $app)
    {
        // Initialize incoming data.
        $path = $request->get("path");
        $this->template = "datasets_delete.html";

        // Create a form with just a submit button.
        $form = $app['form.factory']->createBuilder('form')
            ->getForm();
        $form->handleRequest($request);

        // If form is submitted and (hence) valid, handle file.
        if ($form->isValid()) {
            // Pass request to model layer.
            $this->model->delete($path);
            return $app->redirect("/data");
        }

        return $this->render(
            $app,
            array("title" => "delete dataset", "form" => $form->createView(), "path" => $path)
        );
    }

    /**
     * Handles GET method: CR*Di:Index.
     */
    public function index(Request $request, Application $app)
    {
        return $app['twig']->render(
            "listing.html",
            array(
                "title" => "datasets",
                "items" => $this->model->index(),
                "layout" => "datasets.html",
                "link_prefix" => "data",
            )
        );
    }
}
