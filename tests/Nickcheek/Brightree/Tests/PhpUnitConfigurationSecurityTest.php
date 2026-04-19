<?php

namespace Nickcheek\Brightree\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PhpUnitConfigurationSecurityTest extends TestCase
{
    public function test_phpunit_configuration_does_not_define_ini_values(): void
    {
        $config = simplexml_load_file(__DIR__ . '/../../../../phpunit.xml.dist');

        $this->assertNotFalse($config, 'Failed to parse phpunit.xml.dist');
        $this->assertCount(
            0,
            $config->xpath('/phpunit/php/ini'),
            'Project-level <ini> entries increase exposure to child-process INI injection.',
        );
    }

    public function test_test_suite_does_not_opt_into_separate_php_processes(): void
    {
        $config = simplexml_load_file(__DIR__ . '/../../../../phpunit.xml.dist');

        $this->assertNotFalse($config, 'Failed to parse phpunit.xml.dist');
        $this->assertSame('false', (string) $config['processIsolation']);

        $testFiles = [];
        $iterator  = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__),
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            if (substr($fileInfo->getFilename(), -8) !== 'Test.php') {
                continue;
            }

            $testFiles[] = $fileInfo->getPathname();
        }

        foreach ($testFiles as $file) {
            $contents = file_get_contents($file);

            $this->assertNotFalse($contents, sprintf('Failed to read %s', $file));
            $this->assertSame(
                0,
                preg_match('/^\s*\*\s*@runInSeparateProcess\b/m', $contents),
                $file,
            );
            $this->assertSame(
                0,
                preg_match('/^\s*\*\s*@runTestsInSeparateProcesses\b/m', $contents),
                $file,
            );
        }
    }
}
