<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Metadata\MetadataManager;
use Model\Question\QuestionCorrelationManager;
use Model\Question\QuestionManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Service\QuestionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

class QuestionController extends FOSRestController implements ClassResourceInterface
{
    protected $defaultLocale;

    public function __construct($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }
    /**
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
//    public function getQuestionsAction(Request $request, Application $app)
//    {
//        $locale = $this->getLocale($request, $app['locale.options']['default']);
//        $skip = $request->query->get('skip');
//        $limit = $request->query->get('limit', 10);
//        /* @var QuestionModel $model */
//        $model = $app['questionnaire.questions.model'];
//
//        $questions = $model->getAll($locale, $skip, $limit);
//
//        return $app->json($questions);
//    }

    /**
     * Get next question
     *
     * @Get("/questions/next")
     * @param User $user
     * @param Request $request
     * @param QuestionService $questionService
     * @param QuestionCorrelationManager $questionCorrelationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns next question",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="questions")
     */
    public function getNextQuestionAction(User $user, Request $request, QuestionService $questionService, QuestionCorrelationManager $questionCorrelationManager)
    {
        $locale = $this->getLocale($request);
        $question = $questionService->getNextByUser($user->getId(), $locale);

        $question = $this->setIsRegisterQuestion($question, $user, $questionCorrelationManager);

        return $this->view($question, 200);
    }

    /**
     * Get other user next question
     *
     * @Get("/other-questions/{userId}/next", requirements={"userId"="\d+"})
     * @param integer $userId
     * @param User $user
     * @param Request $request
     * @param QuestionService $questionService
     * @param QuestionCorrelationManager $questionCorrelationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns other user next question",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="questions")
     */
    public function getNextOtherQuestionAction($userId, User $user, Request $request, QuestionService $questionService, QuestionCorrelationManager $questionCorrelationManager)
    {
        $locale = $this->getLocale($request);

        $question = $questionService->getNextByOtherUser($user->getId(), $userId, $locale);

        $question = $this->setIsRegisterQuestion($question, $user, $questionCorrelationManager);

        return $this->view($question, 200);
    }

    /**
     * Get question
     *
     * @Get("/questions/{questionId}", requirements={"questionId"="\d+"})
     * @param integer $questionId
     * @param Request $request
     * @param QuestionManager $questionManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns question",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="questions")
     */
    public function getQuestionAction($questionId, Request $request, QuestionManager $questionManager)
    {
        $locale = $this->getLocale($request);
        $question = $questionManager->getById($questionId, $locale);

        return $this->view($question, 200);
    }

    /**
     * Create question
     *
     * @Post("/questions")
     * @param User $user
     * @param Request $request
     * @param QuestionManager $questionManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created question",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="questions")
     */
    public function postQuestionAction(User $user, Request $request, QuestionManager $questionManager)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        $data['locale'] = $this->getLocale($request);
        $question = $questionManager->create($data);

        return $this->view($question, 201);
    }

    /**
     * Skip question
     *
     * @Post("/questions/{questionId}/skip", requirements={"questionId"="\d+"})
     * @param integer $questionId
     * @param User $user
     * @param Request $request
     * @param QuestionManager $questionManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns skipped question",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="questions")
     */
    public function skipAction($questionId, User $user, Request $request, QuestionManager $questionManager)
    {
        $locale = $this->getLocale($request);
        $question = $questionManager->getById($questionId, $locale);

        $questionManager->skip($questionId, $user->getId());

        return $this->view($question, 201);
    }

    /**
     * Report question
     *
     * @Post("/questions/{questionId}/report", requirements={"questionId"="\d+"})
     * @param integer $questionId
     * @param User $user
     * @param Request $request
     * @param QuestionManager $questionManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="reason", type="string")
     *      )
     * )
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns reported question",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="questions")
     */
    public function reportAction($questionId, User $user, Request $request, QuestionManager $questionManager)
    {
        $reason = $request->request->get('reason');
        $locale = $this->getLocale($request);
        $question = $questionManager->getById($questionId, $locale);

        $questionManager->report($questionId, $user->getId(), $reason);

        return $this->view($question, 201);
    }

    /**
     * Get register questions
     *
     * @Get("/questions/register")
     * @param Request $request
     * @param QuestionService $questionService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns register questions",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="questions")
     */
    public function getRegisterQuestionsAction(Request $request, QuestionService $questionService)
    {
        $locale = $this->getLocale($request);
        $questions = $questionService->getDivisiveQuestions($locale);

        return $this->view($questions, 200);
    }

    protected function setIsRegisterQuestion($question, User $user, QuestionCorrelationManager $questionCorrelationManager)
    {
        $registerModes = isset($question['registerModes']) ? $question['registerModes'] : array();

        if (empty($registerModes)) {
            $question['isRegisterQuestion'] = false;
            unset($question['registerModes']);
            return $question;
        }

        $userId = $user->getId();

        $mode = $questionCorrelationManager->getMode($userId);

        unset($question['registerModes']);

        if ($mode)
        {
            $question['isRegisterQuestion'] = in_array($mode, $registerModes);
        } else {
            $question['isRegisterQuestion'] = $questionCorrelationManager->isDivisiveForAny($question['questionId']);
        }


        return $question;
    }

    protected function getLocale(Request $request)
    {
        $locale = $request->get('locale', $this->defaultLocale);
        $validLocales = MetadataManager::$validLocales;
        if (!in_array($locale, $validLocales)) {
            $locale = $this->defaultLocale;
        }

        return $locale;
    }
}