<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Profile\Profile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
use Model\User\UserManager;
use Model\Profile\ProfileManager;
use Model\Profile\ProfileTagManager;
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
     * @param Profile $profile
     * @return \FOS\RestBundle\View\View
     * @ParamConverter("profile", converter="request_body_converter", class="Model\Profile\Profile")
     * @SWG\Response(
     *     response=200,
     *     description="Returns own profile",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="profiles")
     */
    public function getAction(Profile $profile)
    {
        return $this->view($profile);
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

        if ($search) {
            $search = urldecode($search);
        }

        $result = $profileTagManager->getProfileTagsSuggestion($type, $search, $limit);

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

        return $this->view($profile);
    }

    /**
     * Edit own profile
     *
     * @Put("/profile")
     * @param Profile $profile
     * @return \FOS\RestBundle\View\View
     * @ParamConverter("profile", converter="request_body_converter", class="Model\Profile\Profile")
     * @SWG\Response(
     *     response=201,
     *     description="Returns edited profile.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="profiles")
     */
    public function putAction(Profile $profile)
    {
        return $this->view($profile, 201);
    }
}
