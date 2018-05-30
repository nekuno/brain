<?php

namespace Tests\ApiConsumer\LinkProcessor\Processor;

use PHPUnit\Framework\TestCase;

abstract class AbstractProcessorTest extends TestCase
{
    protected $brainBaseUrl;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../app.php';
        $app['debug'] = true;
        unset($app['exception_handler']);
        $app['session.test'] = true;
        $this->brainBaseUrl = $app['brain_base_url'];

        return $app;
    }

    protected function getEmptyLink()
    {
        return array(
            'title' => null,
            'description' => null,
            'thumbnail' => null,
            'url' => null,
            'id' => null,
            'tags' => array(),
            'created' => null,
            'processed' => true,
            'language' => null,
            'synonymous' => array(),
            'imageProcessed' => null,
            'additionalLabels' => array('Creator', 'LinkFacebook'),
            'lastChecked' => null,
            'lastReprocessed' => null,
            'reprocessedCount' => null,
        );
    }
}
