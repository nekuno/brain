<?php

namespace Controller\User;

use Event\AnswerEvent;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Metadata\MetadataManager;
use Model\Question\Answer;
use Model\Question\AnswerManager;
use Model\Question\QuestionComparePaginatedManager;
use Model\Question\QuestionNotAnsweredPaginatedManager;
use Model\Question\QuestionCorrelationManager;
use Model\Question\QuestionManager;
use Model\Question\UserAnswerPaginatedManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Paginator\Paginator;
use Service\AnswerService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;

class AnswerController extends FOSRestController implements ClassResourceInterface
{
    protected $defaultLocale;

    public function __construct($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Get paginated answers
     *
     * @Get("/answers")
     * @param User $user
     * @param Request $request
     * @param Paginator $paginator
     * @param UserAnswerPaginatedManager $userAnswerPaginatedManager
     * @param QuestionCorrelationManager $questionCorrelationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      default=0
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20
     * )
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns paginated answers.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="answers")
     */
    public function indexAction(Request $request, User $user, Paginator $paginator, UserAnswerPaginatedManager $userAnswerPaginatedManager, QuestionCorrelationManager $questionCorrelationManager)
    {
        $locale = $this->getLocale($request);
        $filters = array('id' => $user->getId(), 'locale' => $locale);
        $result = $paginator->paginate($filters, $userAnswerPaginatedManager, $request);

        foreach ($result['items'] as &$questionData) {
            $question = $questionData['question'];
            $questionData['question'] = $this->setIsRegisterQuestion($question, $user, $questionCorrelationManager);
        }

        return $this->view($result, 200);
    }

    /**
     * Get answer
     *
     * @Get("/answers/{questionId}", requirements={"questionId"="\d+"})
     * @param integer $questionId
     * @param User $user
     * @param Request $request
     * @param AnswerService $answerService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns answer.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="answers")
     */
    public function getAnswerAction($questionId, User $user, Request $request, AnswerService $answerService)
    {
        $locale = $this->getLocale($request);

        $result = $answerService->getUserAnswer($user->getId(), $questionId, $locale);

        return $this->view($result, 200);
    }

    /**
     * Answer question
     *
     * @Post("/answers")
     * @param User $user
     * @param Request $request
     * @param AnswerService $answerService
     * @param QuestionManager $questionManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="questionId", type="integer"),
     *          @SWG\Property(property="answerId", type="integer"),
     *          @SWG\Property(property="acceptedAnswers", type="integer[]"),
     *          @SWG\Property(property="rating", type="integer"),
     *          @SWG\Property(property="isPrivate", type="boolean"),
     *          @SWG\Property(property="explanation", type="string"),
     *          example={ "questionId" = 1000, "answerId" = 1001, "acceptedAnswers" = {1001}, "rating" = 1, "isPrivate" = false, "explanation" = "" },
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
     *     description="Returns answered answer.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="answers")
     */
    public function answerAction(User $user, Request $request, AnswerService $answerService, QuestionManager $questionManager)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        $data['locale'] = $this->getLocale($request);

        $userAnswer = $answerService->answer($data);

        // TODO: Refactor this to listener
        $questionManager->setOrUpdateRankingForQuestion($data['questionId']);

        return $this->view($userAnswer, 201);
    }

    /**
     * Explain answer
     *
     * @Post("/answers/explain")
     * @param User $user
     * @param Request $request
     * @param AnswerService $answerService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="explanation", type="string"),
     *          @SWG\Property(property="questionId", type="integer"),
     *          example={ "questionId" = 1000, "explanation" = "An explanation." },
     *      )
     * )
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns explained answer.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="answers")
     */
    public function explainAction(User $user, Request $request, AnswerService $answerService)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        $data['locale'] = $this->getLocale($request);

        $userAnswer = $answerService->explain($data);

        return $this->view($userAnswer, 200);
    }

    /**
     * Delete user answer
     *
     * @Delete("/answers/{questionId}", requirements={"questionId"="\d+"})
     * @param integer $questionId
     * @param User $user
     * @param Request $request
     * @param AnswerService $answerService
     * @param AnswerManager $answerManager
     * @param QuestionManager $questionManager
     * @param EventDispatcherInterface $dispatcher
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted answer.",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="User answer NOT found.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Answer not deleted.",
     * )
     * @SWG\Response(
     *     response=405,
     *     description="Can't skip answer.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="answers")
     */
    public function deleteAnswerAction(User $user, $questionId, Request $request, AnswerService $answerService, AnswerManager $answerManager, QuestionManager $questionManager, EventDispatcherInterface $dispatcher)
    {
        $locale = $this->getLocale($request);

        try {
            $userAnswer = $answerService->getUserAnswer($user->getId(), $questionId, $locale);
            /** @var Answer $answer */
            $answer = $userAnswer['userAnswer'];
        } catch (NotFoundHttpException $e) {
            return $this->view($e->getMessage(), 404);
        }

        $deletion = $answerManager->deleteUserAnswer($user->getId(), $answer->jsonSerialize());

        if (!$deletion) {
            return $this->view('Answer not deleted', 500);
        }

        $dispatcher->dispatch(\AppEvents::ANSWER_ADDED, new AnswerEvent($user->getId(), $questionId));

        try {
            $questionManager->skip($answer->getQuestionId(), $user->getId());
        } catch (\Exception $e) {
            return $this->view($e->getMessage(), 405);
        }

        return $this->view($answer, 200);
    }

    /**
     * Get compared paginated answers
     *
     * @Get("/answers/compare/{userId}", requirements={"userId"="\d+"})
     * @param integer $userId
     * @param User $user
     * @param Request $request
     * @param Paginator $paginator
     * @param QuestionComparePaginatedManager $questionComparePaginatedManager
     * @param QuestionNotAnsweredPaginatedManager $questionNotAnsweredPaginatedManager
     * @param QuestionCorrelationManager $questionCorrelationManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="showOnlyCommon",
     *      in="query",
     *      type="integer",
     *      default=0
     * )
     * @SWG\Parameter(
     *      name="showOnlyAgreement",
     *      in="query",
     *      type="string",
     *      default=null
     * )
     * @SWG\Parameter(
     *      name="showOnlyOtherNotAnsweredQuestions",
     *      in="query",
     *      type="boolean",
     *      default=false
     * )
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      default=0
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20
     * )
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns paginated answers.",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="One of user ids NOT found.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="answers")
     */
    public function getUserAnswersCompareAction(
        $userId,
        User $user,
        Request $request,
        Paginator $paginator,
        QuestionComparePaginatedManager $questionComparePaginatedManager,
        QuestionNotAnsweredPaginatedManager $questionNotAnsweredPaginatedManager,
        QuestionCorrelationManager $questionCorrelationManager
    ) {
        $locale = $this->getLocale($request);
        $showOnlyCommon = $request->query->get('showOnlyCommon', 0);
        $showOnlyAgreement = $request->query->get('showOnlyAgreement', null);
        $showOnlyOtherNotAnsweredQuestions = $request->query->get('showOnlyOtherNotAnsweredQuestions', false);

        if (null === $userId || null === $user->getId()) {
            return $this->view([], 400);
        }

        //$showOnlyOtherNotAnsweredQuestions uses its own model. $showOnlyAgreement agree or disagree share a model.
        //$showOnlyCommon is not usable with the above and its only kept for legacy compatibility purposes.
        $filters = array('id' => $userId, 'id2' => $user->getId(), 'locale' => $locale, 'showOnlyCommon' => $showOnlyCommon, 'showOnlyAgreement' => $showOnlyAgreement);

        $model = $showOnlyOtherNotAnsweredQuestions ? $questionNotAnsweredPaginatedManager : $questionComparePaginatedManager;

        try {
            $result = $paginator->paginate($filters, $model, $request);

            foreach ($result['items'] as &$questionData) {
                if (empty($question)) {
                    continue;
                }
                $question = $questionData['question'];
                $questionData['question'] = $this->setIsRegisterQuestion($question, $user, $questionCorrelationManager);
            }
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
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
        $question['isRegisterQuestion'] = in_array($mode, $registerModes);

        return $question;
    }
}
