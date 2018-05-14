<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Device\Device;
use Model\Device\DeviceManager;
use Model\Exception\ErrorList;
use Model\Exception\ValidationException;
use Model\User\User;
use Model\User\UserManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Service\AuthService;
use Service\RegisterService;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RouteResource("User")
 */
class UserController extends FOSRestController implements ClassResourceInterface
{
    protected $supportEmails;

    protected $env;

    public function __construct($supportEmails, $env)
    {
        $this->supportEmails = $supportEmails;
        $this->env = $env;
    }

    /**
     * Get own user.
     *
     * @Get("/users")
     * @param User $user
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns own user",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="user", type="object", ref=@Model(type=\Model\User\User::class, groups={"User"}))
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Throws not found exception",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getAction(User $user, UserManager $userManager)
    {
        $user = $userManager->getById($user->getId())->jsonSerialize();

        return $this->view($user, 200);
    }

    /**
     * Get other user.
     *
     * @Get("/users/{slug}")
     * @param string $slug
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns other user by slug",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="user", type="object", ref=@Model(type=\Model\User\User::class, groups={"User"}))
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Throws not found exception",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getOtherAction($slug, UserManager $userManager)
    {
        $userArray = $userManager->getBySlug($slug)->jsonSerialize();
        $userArray = $userManager->deleteOtherUserFields($userArray);

        if (empty($userArray)) {
            return $this->view($userArray, 404);
        }

        return $this->view($userArray, 200);
    }

    /**
     * Get other public user.
     *
     * @Get("/public/users/{slug}")
     * @param string $slug
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns other user by slug",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="user", type="object", ref=@Model(type=\Model\User\User::class, groups={"User"}))
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Throws not found exception",
     * )
     * @SWG\Tag(name="users")
     */
    public function getOtherPublicAction($slug, UserManager $userManager)
    {
        $userArray = $userManager->getPublicBySlug($slug)->jsonSerialize();
        $userArray = $userManager->deleteOtherUserFields($userArray);

        if (empty($userArray)) {
            return $this->view($userArray, 404);
        }

        return $this->view($userArray, 200);
    }

    /**
     * Register new user.
     *
     * @Post("/register")
     * @param Request $request
     * @param RegisterService $registerService
     * @param \Twig_Environment $twig
     * @param \Swift_Mailer $mailer
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"user", "profile", "token", "oauth", "trackingData"},
     *          @SWG\Property(property="user", type="object", ref=@Model(type=\Model\User\User::class, groups={"User"})),
     *          @SWG\Property(property="profile", type="object", ref=@Model(type=\Model\Profile\Profile::class, groups={"Profile"})),
     *          @SWG\Property(property="token", type="string"),
     *          @SWG\Property(property="oauth", type="object", ref=@Model(type=\Model\Token\Token::class, groups={"Token"})),
     *          @SWG\Property(property="trackingData", type="object"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Registers user",
     * )
     * @SWG\Tag(name="users")
     */
    public function postAction(Request $request, RegisterService $registerService, \Twig_Environment $twig, \Swift_Mailer $mailer)
    {
        try {
            $data = $request->request->all();
            if (!isset($data['user']) || !isset($data['profile']) || !isset($data['token']) || !isset($data['oauth']) || !isset($data['trackingData'])) {
                $this->throwRegistrationException('Bad format');
            }
            if (!is_array($data['user']) || !is_array($data['profile']) || !is_string($data['token']) || !is_array($data['oauth']) || !is_array($data['trackingData'])) {
                $this->throwRegistrationException('Bad format');
            }
            $user = $registerService->register($data['user'], $data['profile'], $data['token'], $data['oauth'], $data['trackingData']);
        } catch (\Exception $e) {
            $errorMessage = $this->exceptionMessagesToString($e);
            $message = new \Swift_Message();
            $message
                ->setSubject('Nekuno registration error')
                ->setFrom('enredos@nekuno.com', 'Nekuno')
                ->setTo($this->supportEmails)
                ->setContentType('text/html')
                ->setBody($twig->render('email-notifications/registration-error-notification.html.twig', array(
                    'e' => $e,
                    'errorMessage' => $errorMessage,
                    'data' => json_encode($request->request->all()),
                )));

            $mailer->send($message);

            $exceptionMessage = $this->env === 'dev' ? $errorMessage . ' ' . $e->getFile() . ' ' . $e->getLine() : "Error registering user";
            $this->throwRegistrationException($exceptionMessage);
            return null;
        }

        return $this->view($user, 201);
    }

    /**
     * Edit a user.
     *
     * @Put("/users")
     * @param Request $request
     * @param User $user
     * @param UserManager $userManager
     * @param AuthService $authService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\User\User::class, groups={"User"})
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns edited user and jwt.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function putAction(Request $request, User $user, UserManager $userManager, AuthService $authService)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        $userManager->update($data);
        $jwt = $authService->getToken($data['userId']);

        return $this->view(array(
            'user' => $user,
            'jwt' => $jwt,
        ), 200);
    }

    /**
     * Get username available
     *
     * @Get("/users/available/{username}")
     * @param string $username
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Username is available.",
     * )
     * @SWG\Response(
     *     response=422,
     *     description="Username is NOT available.",
     * )
     * @SWG\Tag(name="users")
     */
    public function availableAction($username, UserManager $userManager)
    {
        $user = $this->getUser();
        if ($user && $user instanceof User && mb_strtolower($username) === $user->getUsernameCanonical()) {
            return $this->view([]);
        }
        $userManager->validateUsername(0, $username);

        return $this->view([]);
    }

    /**
     * Enable/disable user
     *
     * @Get("/users/enable")
     * @param Request $request
     * @param User $user
     * @param UserManager $userManager
     * @param DeviceManager $deviceManager
     * @throws NotFoundHttpException
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="User enabled/disabled.",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="User not found.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function setEnableAction(Request $request, User $user, UserManager $userManager, DeviceManager $deviceManager)
    {
        $enabled = $request->request->get('enabled');
        $userManager->setEnabled($user->getId(), $enabled);

        if (!$enabled) {
            $allDevices = $deviceManager->getAll($user->getId());
            /** @var Device $device */
            foreach ($allDevices as $device) {
                $deviceManager->delete($device->toArray());
            }
        }

        return $this->view([]);
    }

    /**
     * @param $message
     * @throws ValidationException
     */
    protected function throwRegistrationException($message)
    {
        $errorList = new ErrorList();
        $errorList->addError('registration', $message);
        throw new ValidationException($errorList);
    }

    private function exceptionMessagesToString(\Exception $e)
    {
        $errorMessage = $e->getMessage();
        if ($e instanceof ValidationException) {
            foreach ($e->getErrors() as $errors) {
                if (is_array($errors)) {
                    $errorMessage .= "\n" . implode("\n", $errors);
                } elseif (is_string($errors)) {
                    $errorMessage .= "\n" . $errors . "\n";
                }
            }
        }

        return $errorMessage;
    }
}