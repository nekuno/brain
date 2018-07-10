<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\LookUp\LookUpManager;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Swagger\Annotations as SWG;

class LookUpController extends FOSRestController implements ClassResourceInterface
{

    /**
     * Set lookup from web hook
     *
     * @Post("/lookUp/webHook", name="setLookUpFromWebHook")
     * @param Request $request
     * @param LookUpManager $lookUpManager
     * @param LoggerInterface $logger
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=201,
     *     description="Returns empty array.",
     * )
     * @SWG\Tag(name="lookup")
     */
    public function setFromWebHookAction(Request $request, LookUpManager $lookUpManager, LoggerInterface $logger)
    {
        $logger->info(sprintf('Web hook called with content: %s', $request->getContent()));

        $lookUpManager->setFromWebHook($request);

        return $this->view([], 201);
    }
}