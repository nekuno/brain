<?php

namespace Controller\Admin;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Content\ContentReportManager;
use Paginator\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class ContentController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get reported content
     *
     * @Get("/content/reported")
     * @param Request $request
     * @param ContentReportManager $contentReportManager
     * @param Paginator $paginator
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="id",
     *      in="query",
     *      type="integer"
     * )
     * @SWG\Parameter(
     *      name="type[]",
     *      in="query",
     *      type="string",
     *      default="Link"
     * )
     * @SWG\Parameter(
     *      name="disabled",
     *      in="query",
     *      type="boolean"
     * )
     * @SWG\Parameter(
     *      name="order",
     *      in="query",
     *      type="string"
     * )
     * @SWG\Parameter(
     *      name="orderDir",
     *      in="query",
     *      type="string"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns reported contents.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getReportedAction(Request $request, ContentReportManager $contentReportManager, Paginator $paginator)
    {
        $id = $request->get('id', null);
        $type = $request->get('type', array());
        $disabled = $request->get('disabled', null);
        $order = $request->get('order', null);
        $orderDir = $request->get('orderDir', null);

        $filters = array(
            'id' => $id,
            'disabled' => $disabled,
            'order' => $order,
            'orderDir' => $orderDir,
        );
        foreach ($type as $singleType) {
            if (!empty($singleType)) {
                $filters['type'][] = urldecode($singleType);
            }
        }
        try {
            $result = $paginator->paginate($filters, $contentReportManager, $request);
            $result['totals'] = $contentReportManager->countTotal($filters);
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get reported content
     *
     * @Get("/content/reported/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param ContentReportManager $contentReportManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns reported content.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getReportedByIdAction($id, ContentReportManager $contentReportManager)
    {
        $result = $contentReportManager->getById($id);

        return $this->view($result, 200);
    }

    /**
     * Disable content
     *
     * @Post("/content/disable/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param ContentReportManager $contentReportManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns disabled content.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function disableAction($id, ContentReportManager $contentReportManager)
    {
        $result = $contentReportManager->disable($id);

        return $this->view($result, 200);
    }

    /**
     * Enable content
     *
     * @Post("/content/enable/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param ContentReportManager $contentReportManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns enabled content.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function enableAction($id, ContentReportManager $contentReportManager)
    {
        $result = $contentReportManager->enable($id);

        return $this->view($result, 200);
    }
}