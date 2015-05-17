<?php

namespace Controller;

use Model\LinkModel;
use Model\User\RateModel;
use Model\UserModel;
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

            if (empty($link)){
                $link=$linkModel->findLinkByUrl($data['url']);
            }

            $link['resource']=$data['resource'];
            $link['timestamp']=$data['timestamp'];

            if (isset($data['userId'])) {
                /* @var $rateModel RateModel */
                $rateModel = $app['users.rate.model'];
                $rateModel->userRateLink($data['userId'], $link, RateModel::LIKE);
            }


        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($link, empty($createdLink) ? 200 : 201);
    }

    /**
     * Fetch links action. if user
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function fetchLinksAction(Request $request, Application $app)
    {

        $userId = $request->query->get('userId');
        $resource = $request->query->get('resource');

        if (null === $userId || null === $resource) {
            return $app->json(array(), 400);
        }

        /* @var $userModel UserModel*/
        $userModel = $app['users.model'];

        $hasUser = count($userModel->getById($userId)) > 0;
        if (false === $hasUser) {
            return $app->json('User not found', 404);
        }

        $fetcher = $app['api_consumer.fetcher'];

        try {
            $userSharedLinks = $fetcher->fetch($userId, $resource);

        } catch (\Exception $e) {
            return $app->json("An error occurred", 500);
        }

        return $app->json($userSharedLinks, 200);
    }

    /**
     * @param $e
     * @return string
     */
    protected function getError(\Exception $e)
    {

        return sprintf('Error: %s on file %s line %s', $e->getMessage(), $e->getFile(), $e->getLine());
    }
}
