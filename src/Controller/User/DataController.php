<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Token\TokenStatus\TokenStatusManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

class DataController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get user data status
     *
     * @Get("/data/status")
     * @param User $user
     * @param Request $request
     * @param TokenStatusManager $tokenStatusManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="resourceOwner",
     *      in="query",
     *      type="string",
     *      default="facebook"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns user data status.",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="No data status found.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="status")
     */
    public function getStatusAction(User $user, Request $request, TokenStatusManager $tokenStatusManager)
    {
        $resourceOwner = $request->query->get('resourceOwner');
        $userId = $user->getId();

        $statuses = $resourceOwner ? array($tokenStatusManager->getOne($userId, $resourceOwner)) : $tokenStatusManager->getAll($userId);

        if (empty($statuses)) {
            return $this->view([], 404);
        }

        $responseData = array();
        foreach ($statuses as $tokenStatus) {
            $resource = $tokenStatus->getResourceOwner();

            $responseData[$resource] = array(
                'fetched' => $tokenStatus->getFetched(),
                'processed' => $tokenStatus->getProcessed(),
            );
        }

        return $this->view($responseData, 200);
    }

}
