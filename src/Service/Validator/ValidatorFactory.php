<?php

namespace Service\Validator;

use Model\Neo4j\GraphManager;
use Service\MetadataService;

class ValidatorFactory
{
    protected $metadataService;

    protected $graphManager;

    protected $config;

    /**
     * ValidatorFactory constructor.
     * @param GraphManager $graphManager
     * @param MetadataService $metadataService
     * @param $config
     */
    public function __construct(GraphManager $graphManager, MetadataService $metadataService, $config)
    {
        $this->graphManager = $graphManager;
        $this->metadataService = $metadataService;
        $this->config = $config;
    }

    public function build($name)
    {
        $class = $this->getClass($name);
        /** @var Validator $validator */
        $metadata = $this->getMetadata($name);
//        $metadata = $this->metadataManagerFactory->build($name)->getMetadata();
        $validator = new $class($this->graphManager, $metadata);

        return $validator;
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function getClass($name)
    {
        $config = $this->config;
        $defaultValidator = $config['default'];
        $class = isset($config[$name]) ? $config[$name] : $defaultValidator;

        return $class;
    }

    protected function getMetadata($name)
    {
        $anyLocale = 'en';
        switch($name)
        {
            case 'user_filter':
                $metadata = $this->metadataService->getUserFilterMetadata($anyLocale);
                break;
            case 'profile':
                $metadata = $this->metadataService->getProfileMetadata($anyLocale);
                break;
            case 'content_filter':
                $metadata = $this->metadataService->getContentFilterMetadata($anyLocale);
                break;
            default:
                $metadata = $this->metadataService->getBasicMetadata($anyLocale, $name);
                break;
        }

        return $metadata;
    }
}