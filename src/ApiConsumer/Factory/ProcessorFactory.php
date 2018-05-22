<?php

namespace ApiConsumer\Factory;

use ApiConsumer\LinkProcessor\Processor\ProcessorInterface;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;

class ProcessorFactory
{
    private $resourceOwnerFactory;
    private $options;
    private $brainBaseUrl;
    private $goutteClientFactory;

    /**
     * ProcessorFactory constructor.
     * @param ResourceOwnerFactory $resourceOwnerFactory
     * @param GoutteClientFactory $goutteClientFactory
     * @param array $options
     * @param string $brainBaseUrl
     */
    public function __construct(ResourceOwnerFactory $resourceOwnerFactory, GoutteClientFactory $goutteClientFactory, array $options, $brainBaseUrl)
    {
        $this->resourceOwnerFactory = $resourceOwnerFactory;
        $this->goutteClientFactory = $goutteClientFactory;
        $this->options = $options;
        $this->brainBaseUrl = $brainBaseUrl;
    }

    /**
     * @param $processorName
     * @return ProcessorInterface
     */
    public function build($processorName)
    {
        if (!isset($this->options[$processorName])) {
            return $this->getScrapperProcessor();
        }

        $options = $this->options[$processorName];
        if (isset($options['resourceOwner'])) {
            return $this->buildApiProcessor($processorName);
        }

        return $this->buildScrapperProcessor($processorName);
    }

    public function getScrapperProcessor()
    {
        return $this->buildScrapperProcessor(UrlParser::SCRAPPER);
    }

    public function buildScrapperProcessor($processorName)
    {
        $processorClass = $this->options[$processorName]['class'];
        $scraper = new $processorClass($this->goutteClientFactory, $this->brainBaseUrl);

        return $scraper;
    }

    /**
     * @param $processorName
     * @return mixed
     */
    protected function buildApiProcessor($processorName)
    {
        $options = $this->options[$processorName];
        $processorClass = $options['class'];
        $parserClass = $options['parser'];
        $resourceOwner = $this->resourceOwnerFactory->build($options['resourceOwner']);
        $processor = new $processorClass($resourceOwner, new $parserClass(), $this->brainBaseUrl);

        return $processor;
    }
}