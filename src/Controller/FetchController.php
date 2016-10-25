<?php

namespace Controller;

use Model\LinkModel;
use Model\User\RateModel;
use Manager\UserManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FetchController
 *
 * @package Controller
 */
class FetchController
{

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function addLinkAction(Request $request, Application $app)
    {

        $data = $request->request->all();

        try {
            /* @var $linkModel LinkModel */
            $linkModel = $app['links.model'];
            $link = $linkModel->addLink($data);

            if (empty($link)) {
                $link = $linkModel->findLinkByUrl($data['url']);
            }

            if (isset($data['userId'])) {
                /* @var $rateModel RateModel */
                $rateModel = $app['users.rate.model'];
                $rateModel->userRateLink($data['userId'], $link['id'], $data['resource'], $data['timestamp'], RateModel::LIKE);
            }

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($link, empty($createdLink) ? 200 : 201);
    }

}
