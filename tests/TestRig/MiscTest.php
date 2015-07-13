<?php

/**
 * @file
 * Test: aspects of codebase not covered by a single file.
 */

/**
 * @class
 * Test: aspects of codebase not covered by a single file.
 */
class MiscTest extends \PHPUnit_Framework_TestCase
{
    // Possible names for php-cs-fixer, and found one.
    private $commands = array('php-cs-fixer.phar', 'php-cs-fixer');
    private $command = null;

    /**
     * Set up.
     */
    public function setUp()
    {
        // Attempt to look for the PSR-2 linter on the command path.
        // Downloadable from http://get.sensiolabs.org/php-cs-fixer.phar.
        foreach ($this->commands as $command) {
            $this->command = trim(shell_exec("which $command"));
            if ($this->command) {
                return;
            }
        }
    }

    /**
     * Test PSR-2 compliance.
     */
    public function testPSR2Compliance()
    {
        if (!$this->command) {
            $this->markTestSkipped('Needs PSR-2 linter to check compliance; skipping');
        }
        foreach (array("src/", "tests/", "web/index.php") as $path) {
            // Run linter in dry-run mode.
            exec(
                "$this->command fix --level=psr2 --dry-run " . $_SERVER['PWD'] . "/$path",
                $output,
                $return_var
            );
            // If we've got output, pop its first item ("Fixed all files...")
            // and trim whitespace from the rest so the below makes sense.
            if ($output) {
                array_pop($output);
                $output = array_map("trim", $output);
            }
            // Check return code, and if it's nonzero, report the output.
            $this->assertEquals(
                0,
                $return_var,
                "PSR-2 linter reported errors in $path/: " . join("; ", $output)
            );
        }
    }
}
