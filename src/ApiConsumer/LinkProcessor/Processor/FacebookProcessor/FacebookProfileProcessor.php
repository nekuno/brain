<?php

namespace ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;

class FacebookProfileProcessor extends AbstractFacebookProcessor
{
    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $preprocessedLink->getFirstLink()->setProcessed(false);

        if (!$preprocessedLink->getResourceItemId()) {
            throw new CannotProcessException($preprocessedLink->getUrl(), 'Cannot process as a facebook page because for lacking id');
        }

        $id = $preprocessedLink->getResourceItemId();
        $token = $preprocessedLink->getToken();

        return $this->resourceOwner->requestProfile($id, $token);
    }
}