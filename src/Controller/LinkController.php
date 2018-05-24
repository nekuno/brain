<?php

namespace Controller;

use ApiConsumer\Fetcher\ProcessorService;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use ApiConsumer\Images\ImageAnalyzer;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Link\Link;
use Model\Content\Interest;
use Model\Link\LinkManager;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

class LinkController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Check images
     *
     * @Post("/links/images")
     * @param Request $request
     * @param LinkManager $linkManager
     * @param ProcessorService $processorService
     * @param ImageAnalyzer $imageAnalyzer
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="urls", type="string")
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns new interests.",
     * )
     * @SWG\Tag(name="links")
     */
    public function checkImagesAction(Request $request, LinkManager $linkManager, ProcessorService $processorService, ImageAnalyzer $imageAnalyzer)
    {
        $data = $request->request->all();
        $urls = $data['urls'];

        /** @var Link[] $links */
        $links = $linkManager->findLinksByUrls($urls);
        $linksToReprocess = $imageAnalyzer->filterToReprocess($links);

        $preprocessedLinks = array();
        foreach ($linksToReprocess as $link) {
            $preprocessedLink = new PreprocessedLink($link->getUrl());
            $preprocessedLink->setFirstLink($link);
            $preprocessedLinks[] = $preprocessedLink;
        }
        $reprocessedLinks = $processorService->reprocess($preprocessedLinks);

        $interests = array();
        foreach ($reprocessedLinks as $reprocessedLink) {
            $interests[] = Interest::buildFromLinkArray($reprocessedLink->toArray());
        }

        return $this->view($interests, 200);
    }
}