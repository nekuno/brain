<?php

namespace Controller;

use Model\User\User;
use Model\User\UserManager;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Adds User as a valid argument for controllers.
 */
class ArgumentValueResolver implements ArgumentValueResolverInterface
{
    protected $userManager;

    protected $tokenStorage;

    public function __construct(UserManager $userManager, TokenStorageInterface $tokenStorage)
    {
        $this->userManager = $userManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        if (User::class !== $argument->getType()) {
            return false;
        }

        return true;
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $this->checkOwnUser($request);
        $this->checkOtherUser($request);

        yield $this->tokenStorage->getToken()->getUser();
    }

    protected function checkOtherUser(Request $request)
    {
        list($otherUserId, $otherUser) = $this->getUser($request);

        if ($otherUser instanceof User && !$otherUser->isEnabled()){
            //TODO: refactor to userManager->manageUserNotFound
            throw new NotFoundHttpException(sprintf('User "%s" not found', $otherUserId));
        }
    }

    protected function getUser(Request $request)
    {
        $attributes = $request->attributes;

        $otherUser = null;
        $otherUserId = null;
        if ($attributes->get('userId')){
            $otherUserId= $attributes->get('userId');
            $otherUser = $this->userManager->getById($otherUserId);
        }
        if ($attributes->get('to')){
            $otherUserId = $attributes->get('to');
            $otherUser = $this->userManager->getById($otherUserId);
        }
        if ($attributes->get('from')){
            $otherUserId = $attributes->get('from');
            $otherUser = $this->userManager->getById($otherUserId);
        }
        if ($attributes->get('slug')){
            $otherUserId = $attributes->get('slug');
            $otherUser = $this->userManager->getBySlug($otherUserId);
        }

        return array($otherUserId, $otherUser);
    }

    protected function checkOwnUser(Request $request)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user instanceof User) {
            throw new AuthenticationException('Not Authenticated.');
        }
        if (!$this->isUserCorrect($this->tokenStorage->getToken()->getUser(), $request->getPathInfo())) {
            throw new AuthenticationException('User is disabled');
        }
    }

    protected function isUserCorrect(User $user = null, $path)
    {
        $excludedPaths = array('/users/enable', '/users');
        $mustCheckEnabled = !in_array($path, $excludedPaths);

        if (!$user || $mustCheckEnabled && !$user->isEnabled()) {
            return false;
        }

        return true;
    }
}