<?php

namespace Controller\Admin;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\User\UserTrackingManager;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/admin")
 */
class UserTrackingController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get all users tracking data
     *
     * @Get("/users/tracking")
     * @param UserTrackingManager $userTrackingManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns all users tracking data.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getAllAction(UserTrackingManager $userTrackingManager)
    {
        $result = $userTrackingManager->getAll();

        return $this->view($result, 200);
    }

    /**
     * Get user tracking data
     *
     * @Get("/users/{id}/tracking", requirements={"id"="\d+"})
     * @param integer $id
     * @param UserTrackingManager $userTrackingManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns user tracking data.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getAction($id, UserTrackingManager $userTrackingManager)
    {
        $result = $userTrackingManager->get($id);

        return $this->view($result, 200);
    }

    /**
     * Get users tracking data in a csv file
     *
     * @Get("/users/csv")
     * @param UserTrackingManager $userTrackingManager
     * @return string
     * @SWG\Response(
     *     response=200,
     *     description="Returns csv file.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getCsvAction(UserTrackingManager $userTrackingManager)
    {
        $array = $userTrackingManager->getUsersDataForCsv();
        $this->downloadSendHeaders("data_export_" . date("Y-m-d") . ".csv");

        return new Response($this->array2csv($array), 200);
    }

    private function array2csv(array &$array)
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }

    private function downloadSendHeaders($filename) {
        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }
}