<?php


namespace Controller\User;

use Doctrine\ORM\EntityManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class DataController
{

    public function getStatusAction(Request $request, Application $app)
    {

        $userId = $request->get('userId');

        $resourceOwner = $request->query->get('resourceOwner');

        /** @var EntityManager $em */
        $em = $app['orm.ems']['mysql_brain'];
        $dataStatusRepository = $em->getRepository('\Model\Entity\DataStatus');

        $criteria['userId'] = $userId;

        if ($resourceOwner) {
            $criteria['resourceOwner'] = $resourceOwner;
        }

        $dataStatus = $dataStatusRepository->findBy($criteria);

        if (null === $dataStatus) {
            return $app->json(null, 404);
        }

        $responseData = array();
        foreach ($dataStatus as $row) {
            $responseData[$row->getResourceOwner()] = array(
                'fetched' => $row->getFetched(),
                'processed' => $row->getProcessed()
            );
        }

        return $app->json($responseData, 200);
    }

}
