<?php

namespace Controller\Admin;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Question\Admin\QuestionsAdminPaginatedManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Paginator\Paginator;
use Service\QuestionService;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class QuestionController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get paginated questions
     *
     * @Get("/questions")
     * @param Request $request
     * @param Paginator $paginator
     * @param QuestionsAdminPaginatedManager $questionsAdminPaginated
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es",
     * )
     * @SWG\Parameter(
     *      name="order",
     *      in="query",
     *      type="string",
     * )
     * @SWG\Parameter(
     *      name="orderDir",
     *      in="query",
     *      type="string",
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
     *     description="Returns questions.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getQuestionsAction(Request $request, Paginator $paginator, QuestionsAdminPaginatedManager $questionsAdminPaginated)
    {
        $locale = $request->get('locale', 'es');
        $order = $request->get('order', null);
        $orderDir = $request->get('orderDir', null);
        $filters = array(
            'locale' => $locale,
            'order' => $order,
            'orderDir' => $orderDir,
        );

        $result = $paginator->paginate($filters, $questionsAdminPaginated, $request);

        return $this->view($result, 200);
    }

    /**
     * Get question
     *
     * @Get("/questions/{questionId}", requirements={"questionId"="\d+"})
     * @param integer $questionId
     * @param QuestionService $questionService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns question.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getQuestionAction($questionId, QuestionService $questionService)
    {
        $question = $questionService->getOneAdmin($questionId);

        return $this->view($question, 200);
    }

    /**
     * Create question
     *
     * @Post("/questions")
     * @param Request $request
     * @param QuestionService $questionService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="answerTexts", type="array[]"),
     *          @SWG\Property(property="questionTexts", type="array[]"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created question.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function createQuestionAction(Request $request, QuestionService $questionService)
    {
        $data = $request->request->all();
        $created = $questionService->createQuestion($data);

        return $this->view($created, 201);
    }

    /**
     * Edit question
     *
     * @Put("/questions/{questionId}", requirements={"questionId"="\d+"})
     * @param integer $questionId
     * @param Request $request
     * @param QuestionService $questionService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="answerTexts", type="array[]"),
     *          @SWG\Property(property="questionTexts", type="array[]"),
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns edited question.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function updateQuestionAction($questionId, Request $request, QuestionService $questionService)
    {
        $data = $request->request->all();
        $data['questionId'] = $questionId;

        $updated = $questionService->updateQuestion($data);

        return $this->view($updated, 200);
    }

    /**
     * Delete question
     *
     * @Delete("/questions/{questionId}", requirements={"questionId"="\d+"})
     * @param integer $questionId
     * @param QuestionService $questionService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted question.",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Question NOT found.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function deleteQuestionAction($questionId, QuestionService $questionService)
    {
        $deleted = $questionService->deleteQuestion($questionId);
        $code = $deleted ? 201 : 404;

        return $this->view($deleted, $code);
    }
}