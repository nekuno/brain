<?php

namespace Controller\Admin;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Relations\RelationsManager;
use Model\Relations\RelationsPaginatedManager;
use Model\User\UserDisabledPaginatedManager;
use Model\User\UserManager;
use Paginator\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class UserReportController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get paginated reported users
     *
     * @Get("/users/reported")
     * @param Request $request
     * @param Paginator $paginator
     * @param RelationsPaginatedManager $relationsPaginatedManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="from",
     *      in="query",
     *      type="integer",
     * )
     * @SWG\Parameter(
     *      name="to",
     *      in="query",
     *      type="integer",
     * )
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      default=0,
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20,
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns reported users.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getReportedAction(Request $request, Paginator $paginator, RelationsPaginatedManager $relationsPaginatedManager)
    {
        $from = $request->get('from', null);
        $to = $request->get('to', null);
        $filters = array(
            'relation' => RelationsManager::REPORTS,
            'from' => $from,
            'to' => $to
        );

        try {
            $result = $paginator->paginate($filters, $relationsPaginatedManager, $request);
            $result['totals'] = $relationsPaginatedManager->countTotal($filters);
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get paginated disabled users
     *
     * @Get("/users/disabled")
     * @param Request $request
     * @param Paginator $paginator
     * @param UserDisabledPaginatedManager $userDisabledPaginatedManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      default=0,
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20,
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns disabled users.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getDisabledAction(Request $request, Paginator $paginator, UserDisabledPaginatedManager $userDisabledPaginatedManager)
    {
        $filters = array();

        try {
            $result = $paginator->paginate($filters, $userDisabledPaginatedManager, $request);
            $result['totals'] = $userDisabledPaginatedManager->countTotal($filters);
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get disabled user
     *
     * @Get("/users/disabled/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param UserDisabledPaginatedManager $userDisabledPaginatedManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns disabled user.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getDisabledByIdAction($id, UserDisabledPaginatedManager $userDisabledPaginatedManager)
    {
        $result = $userDisabledPaginatedManager->getById($id);

        return $this->view($result, 200);
    }

    /**
     * Enable user
     *
     * @Post("/users/{id}/enable", requirements={"id"="\d+"})
     * @param integer $id
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns true if enabled.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function enableAction($id, UserManager $userManager)
    {
        return $this->setEnabled($userManager, $id, true);
    }

    /**
     * Disable user
     *
     * @Post("/users/{id}/disable", requirements={"id"="\d+"})
     * @param integer $id
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns true if disabled.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function disableAction($id, UserManager $userManager)
    {
        return $this->setEnabled($userManager, $id, false);
    }

    protected function setEnabled(UserManager $userManager, $id, $enabled)
    {
        $enabledSet = $userManager->setEnabled($id, $enabled, true);
        $canReenableSet = $userManager->setCanReenable($id, $enabled);

        return $this->view($enabledSet && $canReenableSet, 200);
    }
}