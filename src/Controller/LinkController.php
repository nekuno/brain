<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Controller;


use ApiConsumer\LinkProcessor\ImageAnalyzer;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class LinkController
{
    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function checkImagesAction(Request $request, Application $app)
    {
        $data = $request->request->all();
        $urls = $data['urls'];

        $linkModel = $app['links.model'];
        $fetcherService = $app['api_consumer.fetcher'];
        /** @var ImageAnalyzer $imageAnalyzer */
        $imageAnalyzer = $app['api_consumer.link_processor.image_analyzer'];

        $links = $linkModel->findLinksByUrl($urls);
        $linksToReprocess = $imageAnalyzer->filterToReprocess($links);


        $reprocessedLinks = $fetcherService->reprocessLinks($linksToReprocess);

        return $app->json($reprocessedLinks);
    }
}