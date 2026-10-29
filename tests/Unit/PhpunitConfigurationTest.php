<?php

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class PhpunitConfigurationTest extends TestCase
{
    private function configuration(): DOMDocument
    {
        $document = new DOMDocument();
        $this->assertTrue($document->load(dirname(__DIR__, 2) . '/phpunit.xml'));

        return $document;
    }

    public function test_runner_configuration_validates_against_the_installed_schema(): void
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = $this->configuration();
            $valid = $document->schemaValidate(dirname(__DIR__, 2) . '/vendor/phpunit/phpunit/phpunit.xsd');
            $messages = array_map(static fn ($error) => trim($error->message), libxml_get_errors());
            $this->assertTrue($valid, implode("\n", $messages));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function test_runner_keeps_in_memory_defaults_and_does_not_silence_issues(): void
    {
        $document = $this->configuration();
        $root = $document->documentElement;
        $this->assertSame('true', $root->getAttribute('failOnAllIssues'));
        $this->assertSame('false', $root->getAttribute('cacheResult'));
        $xpath = new DOMXPath($document);
        $this->assertSame('512M', $xpath->evaluate('string(/phpunit/php/ini[@name="memory_limit"]/@value)'));
        foreach (['APP_ENV' => 'testing', 'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
            'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync'] as $name => $value) {
            $this->assertSame($value, $xpath->evaluate('string(/phpunit/php/server[@name="' . $name . '"]/@value)'), $name);
        }
    }
}
