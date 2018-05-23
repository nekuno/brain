<?php

namespace Controller\Admin\EnterpriseUser;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\EnterpriseUser\CommunityManager;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class CommunityController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get group communities
     *
     * @Get("/enterpriseUsers/groups/{id}/communities", requirements={"id"="\d+"})
     * @param integer $id
     * @param CommunityManager $communityManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns group communities.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getByGroupAction($id, CommunityManager $communityManager)
    {
        $communities = $communityManager->getByGroup($id);

        return $this->view($communities, 200);
    }
}