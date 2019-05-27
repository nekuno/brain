<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Service\ProfileService;
use Swagger\Annotations as SWG;
use Model\User\UserManager;
use Model\Profile\ProfileManager;
use Model\Profile\ProfileTagManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Service\MetadataService;
use Symfony\Component\HttpFoundation\Request;

class ProfileController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get own profile
     *
     * @Get("/profile")
     * @param User $user
     * @param ProfileManager $profileManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns own profile",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="profile", type="object", ref=@Model(type=\Model\Profile\Profile::class, groups={"Profile"}))
     *     )
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="profiles")
     */
    public function getAction(User $user, ProfileManager $profileManager)
    {
        $profile = $profileManager->getById($user->getId());

        return $this->view($profile->jsonSerialize());
    }

    /**
     * Get profile data for other user´s page
     *
     * @Get("/profile/{slug}/page")
     * @param string $slug

     * @param User $user
     * @param ProfileService $profileService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns profile data",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="matching", type="integer"),
     *         @SWG\Property(property="similarity", type="integer"),
     *         @SWG\Property(property="location", type="object"),
     *
     *     )
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="profiles")
     */
    public function getOtherPageAction($slug, User $user, ProfileService $profileService)
    {
        $otherPage = $profileService->getOtherPage($slug, $user);

        return $this->view($otherPage);
    }

    /**
     * Get profile data for other user´s page
     *
     * @Get("/profile/page")
     * @param User $user
     * @param ProfileService $profileService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns own profile data",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="location", type="object"),
     *
     *     )
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="profiles")
     */
    public function getOwnPageAction(User $user, ProfileService $profileService)
    {
        $ownPage = $profileService->getOwnPage($user);

        return $this->view($ownPage);
    }

    /**
     * Get profile metadata
     *
     * @Get("/profile/metadata")
     * @param Request $request
     * @param MetadataService $metadataService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="en",
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns profile metadata.",
     * )
     * @SWG\Tag(name="profiles")
     */
    public function getMetadataAction(Request $request, MetadataService $metadataService)
    {
        $locale = $request->query->get('locale', 'en');

        $metadata = $metadataService->getProfileMetadataWithChoices($locale);

        return $this->view($metadata);
    }

    /**
     * Get profile categories metadata
     *
     * @Get("/profile/categories")
     * @param Request $request
     * @param MetadataService $metadataService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="en",
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns profile categories metadata.",
     * )
     * @SWG\Tag(name="profiles")
     */
    public function getCategoriesAction(Request $request, MetadataService $metadataService)
    {
        $locale = $request->query->get('locale', 'en');

        $categories = $metadataService->getCategoriesMetadata($locale);

        return $this->view($categories);
    }

    /**
     * Get profile filters metadata
     *
     * @Get("/profile/filters")
     * @param Request $request
     * @param MetadataService $metadataService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="en",
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns profile filters metadata.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="profiles")
     */
    public function getFiltersAction(Request $request, MetadataService $metadataService)
    {
        $locale = $request->query->get('locale', 'en');

        $filters = $metadataService->getUserFilterMetadata($locale);

        return $this->view($filters);
    }

    /**
     * Get profile tags metadata
     *
     * @Get("/profile/tags/{type}")
     * @param string $type
     * @param Request $request
     * @param ProfileTagManager $profileTagManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="search",
     *      in="query",
     *      type="string",
     *      default="",
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=3,
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns profile tags metadata.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="profiles")
     */
    public function getProfileTagsAction($type, Request $request, ProfileTagManager $profileTagManager)
    {
        $search = $request->get('search', '');
        $limit = $request->get('limit', 3);
        $locale = $request->query->get('locale', 'en');

        if ($search) {
            $search = urldecode($search);
        }

        $result = $profileTagManager->getProfileTagsSuggestion($type, $search, $limit, $locale);

        return $this->view($result);
    }

    /**
     * Get other profile
     *
     * @Get("/profile/{slug}")
     * @param string $slug
     * @param UserManager $userManager
     * @param ProfileManager $profileManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns other profile",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="profile", type="object", ref=@Model(type=\Model\Profile\Profile::class, groups={"Profile"}))
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="User not found",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="profiles")
     */
    public function getOtherAction($slug, UserManager $userManager, ProfileManager $profileManager)
    {
        $userId = $userManager->getBySlug($slug)->getId();
        $profile = $profileManager->getById($userId);

        return $this->view($profile->jsonSerialize());
    }

    /**
     * Edit own profile
     *
     * @Put("/profile")
     * @param Request $request
     * @param User $user
     * @param ProfileManager $profileManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\Profile\Profile::class, groups={"Profile"})
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns edited profile.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="profiles")
     */
    public function putAction(Request $request, User $user, ProfileManager $profileManager)
    {
        $profile = $profileManager->update($user->getId(), $request->request->all());

        return $this->view($profile->jsonSerialize());
    }
}
