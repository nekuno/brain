<?php

namespace Controller\User;

use Doctrine\ORM\EntityManager;
use Model\Entity\DataStatus;
use Model\User\TokensModel;
use Model\User;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class DataController
{
    /**
     * @param Request $request
     * @param Application $app
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getStatusAction(Request $request, Application $app, User $user)
    {
        $resourceOwner = $request->query->get('resourceOwner');

        /** @var EntityManager $em */
        $em = $app['orm.ems']['mysql_brain'];
        $dataStatusRepository = $em->getRepository('\Model\Entity\DataStatus');

        $criteria['userId'] = $user->getId();

        if ($resourceOwner) {
            $criteria['resourceOwner'] = $resourceOwner;
        }

        $dataStatus = $dataStatusRepository->findBy($criteria);

        if (null === $dataStatus) {
            return $app->json(null, 404);
        }

        /* @var TokensModel $tokensModel */
        $tokensModel = $app['users.tokens.model'];
        $connectedNetworks = $tokensModel->getConnectedNetworks($user->getId());

        $responseData = array();
        /* @var $row DataStatus */
        foreach ($dataStatus as $row) {
            $resource = $row->getResourceOwner();

            if (!in_array($resource, $connectedNetworks)) {
                continue;
            }
            $responseData[$resource] = array(
                'fetched' => $row->getFetched(),
                'processed' => $row->getProcessed(),
            );
        }

        return $app->json($responseData, 200);
    }

}
