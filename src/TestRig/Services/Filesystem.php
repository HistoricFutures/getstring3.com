<?php

/**
 * @file
 * Filesystem library calls.
 */

namespace TestRig\Services;

/**
 * @class
 * Filesystem methods.
 */
class Filesystem
{
    /**
     * Remove directory and all contents recursively.
     *
     * @param string $dir
     *   Top-level directory, to be removed with contents.
     */
    public static function removeDirectory($dir)
    {
        // Use directory recursion to get list of files, children first.
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        // Loop over recursive list and pick a relevant remove callback.
        foreach ($files as $file) {
            $callback = ($file->isDir() ? 'rmdir' : 'unlink');
            $callback($file->getRealPath());
        }

        // Recursive iterators don't include the directory, so remove.
        rmdir($dir);
    }

    /**
     * Execute a shell command and provide STDOUT, STDERR etc.
     *
     * See http://stackoverflow.com/a/25879953/327153 .
     *
     * @param string $cmd
     *                    Shell command.
     * @param string &stdout = null
     *   Target string for STDOUT.
     * @param string &stderr = null
     *   Target string for STDERR.
     *
     * @return int
     *             Exit code.
     */
    public static function execCommand($cmd, &$stdout = null, &$stderr = null)
    {
        $proc = proc_open($cmd, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($proc);
    }

    /**
     * Retrieve information about the current codebase's git revision.
     *
     * @param string $dir = null
     *   Directory to query for git information. Defaults to pwd.
     * @return array
     *   Array with keys revision, branch and tag, plus any error reporting.
     */
    public static function retrieveGitstamp($dir = null)
    {
        // Ensure input and output defined.
        if ($dir === null) {
            $dir = getcwd();
        }
        $output = [];

        // Assemble a "master command" for all git work.
        $dir = escapeshellarg($dir);
        $gitCommand = "git --work-tree=$dir";
        // Subcommands for the particular return values.
        $gitSubCommands = [
            'revision' => 'rev-parse HEAD',
            'branch' => 'rev-parse --abbrev-ref HEAD',
            // Tag least likely to work (if no tags) so put last.
            'tag' => 'describe --tags',
        ];

        // Loop over subcommands; quit if there's an error.
        foreach ($gitSubCommands as $outputType => $gitSubCommand) {
            $output['exitCode'] = static::execCommand(
                "$gitCommand $gitSubCommand",
                $outputText,
                $output['error']
            );
            // Trimming is just so much easier in PHP than bash!
            $output[$outputType] = trim($outputText);

            // Any problems, quit now so they can be diagnosed.
            if ($output['exitCode']) {
                break;
            }
        }

        // If the error is 128 and tag === '', it's not really an error,
        // just that no tags have been created yet in this repository.
        if (array_key_exists('tag', $output) && $output['exitCode'] === 128) {
            $output['error'] = "";
            $output['exitCode'] = 0;
        }

        return $output;
    }
}
