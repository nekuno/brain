<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Swagger\Annotations as SWG;
use Model\User\UserManager;
use Model\Relations\RelationsManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;

class RelationsController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get all relations
     *
     * @Get("/{relation}", requirements={"relation"="(blocks|favorites|likes|dislikes|ignores|reports)"})
     * @param string $relation
     * @param User $user
     * @param RelationsManager $relationsManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns all relations",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="relations")
     */
    public function cgetAction($relation, User $user, RelationsManager $relationsManager)
    {
        $relation = mb_strtoupper($relation);
        $result = $relationsManager->getAll($relation, $user->getId());

        return $this->view($result);
    }

    /**
     * Get all relations to other user
     *
     * @Get("/relations/{slugTo}")
     * @param @param string $slugTo
     * @param User $user
     * @param UserManager $userManager
     * @param RelationsManager $relationsManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns all relations to other user",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="relations")
     */
    public function getAllAction($slugTo, User $user, UserManager $userManager, RelationsManager $relationsManager)
    {
        $to = $userManager->getBySlug($slugTo)->getId();

        $result = array();
        foreach (RelationsManager::getRelations() as $relation) {
            if (!empty($relationsManager->get($user->getId(), $to, $relation))) {
                $result[$relation] = true;
            } else {
                $result[$relation] = false;
            }
        }

        return $this->view($result);
    }

    /**
     * Get relation to other user
     *
     * @Get("/{relation}/{slugTo}", requirements={"relation"="(blocks|favorites|likes|dislikes|ignores|reports)"})
     * @param @param string $slugTo
     * @param @param string $relation
     * @param User $user
     * @param UserManager $userManager
     * @param RelationsManager $relationsManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns relation to other user",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="relations")
     */
    public function getAction($slugTo, $relation, User $user, UserManager $userManager, RelationsManager $relationsManager)
    {
        $relation = mb_strtoupper($relation);
        $to = $userManager->getBySlug($slugTo)->getId();
        $result = $relationsManager->get($user->getId(), $to, $relation);
        $result = $this->unsetSensitiveData($result, $userManager);

        return $this->view($result);
    }


    /**
     * Get relations from other user
     *
     * @Get("/other-relations/{slugFrom}")
     * @param @param string $slugTo
     * @param User $user
     * @param UserManager $userManager
     * @param RelationsManager $relationsManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns relations from other user",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="relations")
     */
    public function getOtherAction($slugFrom, User $user, UserManager $userManager, RelationsManager $relationsManager)
    {
        $from = $userManager->getBySlug($slugFrom)->getId();

        $result = array();
        foreach (RelationsManager::getRelations() as $relation) {
            if (!empty($relationsManager->get($from, $user->getId(), $relation))) {
                $result[$relation] = true;
            } else {
                $result[$relation] = false;
            }
        }

        return $this->view($result);
    }

    /**
     * Create relation to other user
     *
     * @Post("/{relation}/{slugTo}", requirements={"relation"="(blocks|favorites|likes|dislikes|ignores|reports)"})
     * @param @param string $slugTo
     * @param @param string|null $relation
     * @param Request $request
     * @param User $user
     * @param UserManager $userManager
     * @param RelationsManager $relationsManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns created relation",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="relations")
     */
    public function postAction($slugTo, $relation, Request $request, User $user, UserManager $userManager, RelationsManager $relationsManager)
    {
        $relation = mb_strtoupper($relation);
        $to = $userManager->getBySlug($slugTo)->getId();

        $data = $request->request->all();

        $result = $relationsManager->create($user->getId(), $to, $relation, $data);
        $result = $this->unsetSensitiveData($result, $userManager);

        return $this->view($result);
    }

    /**
     * Delete relation to other user
     *
     * @Delete("/{relation}/{slugTo}", requirements={"relation"="(blocks|favorites|likes|dislikes|ignores|reports)"})
     * @param @param string $slugTo
     * @param @param string|null $relation
     * @param User $user
     * @param UserManager $userManager
     * @param RelationsManager $relationsManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted relation",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="relations")
     */
    public function deleteAction($slugTo, $relation, User $user, UserManager $userManager, RelationsManager $relationsManager)
    {
        $relation = mb_strtoupper($relation);
        $to = $userManager->getBySlug($slugTo)->getId();

        $result = $relationsManager->remove($user->getId(), $to, $relation);
        $result = $this->unsetSensitiveData($result, $userManager);

        return $this->view($result);
    }

    protected function unsetSensitiveData(array $result, UserManager $userManager)
    {
        if (isset($result['from'])){
            $result['from'] = $userManager->deleteOtherUserFields($result['from']);
        }
        if (isset($result['to'])){
            $result['to'] = $userManager->deleteOtherUserFields($result['to']);
        }

        return $result;
    }
}
