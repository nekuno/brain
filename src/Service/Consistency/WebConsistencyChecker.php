<?php

namespace Service\Consistency;

use Model\Exception\ErrorList;
use Service\Consistency\ConsistencyErrors\ConsistencyError;

class WebConsistencyChecker extends ConsistencyChecker
{
    protected function checkProperties(array $properties, $id, array $propertyRules)
    {
        $isWellProcessed = isset($properties['processed']) && $properties['processed'] != 0;
        if (!$isWellProcessed) {
            return;
        }

        $hasTitle = isset($properties['title']);
        $hasThumbnail = isset($properties['thumbnail']);

        if (!$hasTitle && !$hasThumbnail)
        {
            $error = new ConsistencyError();
            $error->setMessage(sprintf('Web with id %d is marked as processed but lacks title and thumbnail', $id));

            $errorList = new ErrorList();
            $errorList->addError('processed', $error);
            $this->throwErrors($errorList, $id);
        }
    }

}