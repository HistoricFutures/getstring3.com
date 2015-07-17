<?php

/**
 * @file
 * Controllers to handle data generation CRUDI methods.
 */

namespace TestRig\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TestRig\Models\Algorithm;

/**
 * @class
 * Controller to handle data generation CR*Di methods.
 */
class AlgorithmController
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
        $this->model = new Algorithm();
    }

    /**
     * Handles GET/POST method: CR*Di:Create.
     */
    public function create(Request $request, Application $app)
    {
        // Create a form with just an upload widget.
        $form = $app['form.factory']->createBuilder('form')
            ->add('attachment', 'file', array('label' => 'Choose an algorithm file', 'required' => true))
            ->add('format', 'choice', array(
                'label' => 'Choose a file format',
                'required' => true,
                'choices' => array('php' => 'PHP', 'py' => 'Python'),
            ))
            ->getForm();
        $form->handleRequest($request);

        // If form is submitted and (hence) valid, handle file.
        if ($form->isValid()) {
            // Pass file to model layer.
            $path = $this->model->create(
                $form['format']->getData(),
                $form['attachment']->getData()
            );
            return $app->redirect("/algo/$path");
        }

        return $app['twig']->render(
            "create.html",
            array(
                "title" => "upload new algorithm",
                "form" => $form->createView(),
                "layout" => "algorithms.html",
            )
        );
    }

    /**
     * Handles GET method: CR*Di:Read.
     */
    public function read(Request $request, Application $app)
    {
        // Initialize incoming data.
        $path = $request->get("path");

        // Get metadata for dataset and inject variables for Twig.
        $metadata = $this->model->read($path);
        $metadata["title"] = "view algorithm";
        $metadata["path"] = $path;

        return $app['twig']->render("algorithms_single.html", $metadata);
    }

    /**
     * Handles GET/POST method: CR*Di:Delete.
     */
    public function delete(Request $request, Application $app)
    {
        // Initialize incoming data.
        $path = $request->get("path");

        // Create a form with just a submit button.
        $form = $app['form.factory']->createBuilder('form')
            ->getForm();
        $form->handleRequest($request);

        // If form is submitted and (hence) valid, handle file.
        if ($form->isValid()) {
            // Pass request to model layer.
            $this->model->delete($path);
            return $app->redirect("/algo");
        }

        return $app['twig']->render(
            "delete.html",
            array(
                "title" => "delete algorithm",
                "form" => $form->createView(),
                "path" => $path,
                "layout" => "algorithms.html",
                "link_prefix" => "algo",
            )
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
                "title" => "algorithms",
                "items" => $this->model->index(),
                "layout" => "algorithms.html",
                "link_prefix" => "algo",
            )
        );
    }
}
