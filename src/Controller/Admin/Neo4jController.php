<?php

namespace Controller\Admin;

use Service\GraphExploreService;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;


/**
 * @Route("/admin")
 */
class Neo4jController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get neo4j graph data for viewing
     *
     * @Get("/neo4j")
     * @param Request $request
     * @param GraphExploreService $graphExploreService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="scenario",
     *      in="query",
     *      type="string",
     *      default="similarity",
     * )
     * @SWG\Parameter(
     *     name="user1Id",
     *     in="query",
     *     type="string",
     *     default="1",
     * )
     * @SWG\Parameter(
     *     name="user2Id",
     *     in="query",
     *     type="string",
     *     default="2",
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns neo4j graph data for viewing.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getDataAction(Request $request, GraphExploreService $graphExploreService)
    {
        $scenario = $request->query->get('scenario');

        switch ($scenario) {
            case 'similarity':
                $user1Id = $request->query->get('user1Id');
                $user2Id = $request->query->get('user2Id');
                $data = $graphExploreService->getSimilarity($user1Id, $user2Id);
                break;
            default:
                $data = array();
                break;
        }

        return $this->view($data, 200);
    }
}