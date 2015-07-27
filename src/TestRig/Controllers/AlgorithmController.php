<?php

/**
 * @file
 * Controllers to handle data generation CRUDI methods.
 */

namespace TestRig\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TestRig\Models\Algorithm;
use TestRig\Models\Dataset;
use TestRig\Models\Executor;

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

    /**
     * Handles GET/POST method: run algorithm against a dataset.
     */
    public function run(Request $request, Application $app)
    {
        // Initialize incoming data.
        $path = $request->get("path");

        // Get list of datasets for dropdown.
        $dataset = new Dataset();
        $datasetDropdown = array();
        foreach ($dataset->index() as $datasetDir) {
            $datasetDropdown[$datasetDir] = $datasetDir;
        }

        // Create a form with just a submit button.
        $form = $app['form.factory']->createBuilder('form')
            ->add('dataset', 'choice', array(
                'label' => 'Choose a dataset',
                'required' => true,
                'choices' => $datasetDropdown,
            ))
            ->getForm();
        $form->handleRequest($request);

        // If form is submitted and (hence) valid, execute this algorithm.
        if ($form->isValid()) {
            $executor = new Executor();
            $executor->read($path);

            $response = $executor->run(
                $path,
                $dataset->fullPath($form['dataset']->getData())
            );

            if ($response['exitCode'] === 0) {
                $stream = function () use ($response) {
                    echo $response['stdout'];
                };

                return $app->stream(
                    $stream,
                    200,
                    array(
                        'Content-Type' => 'text/plain',
                        'Content-length' => mb_strlen($response['stdout'], '8bit'),
                        'Content-Disposition' => 'attachment; filename="output.txt"',
                    )
                );
            }

            // Otherwise, any errors, abort request and dump stdout/stderr.
            $app->abort(500, "Execution errors: " . var_export($response, TRUE));
        }

        return $app['twig']->render(
            "run.html",
            array(
                "title" => "run algorithm",
                "form" => $form->createView(),
                "path" => $path,
                "layout" => "algorithms.html",
                "link_prefix" => "algo",
                "submit_text" => "Run algorithm",
            )
        );
    }
}
