<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Token\Token;
use Nelmio\ApiDocBundle\Annotation\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;

class TokensController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Create token
     *
     * @Post("/tokens/{resourceOwner}")
     * @param Token $token
     * @return \FOS\RestBundle\View\View
     * @ParamConverter("token", converter="request_body_converter", class="Model\Token\Token")
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"oauthToken"},
     *          @SWG\Property(property="oauthToken", type="string"),
     *          @SWG\Property(property="oauthTokenSecret", type="string"),
     *          @SWG\Property(property="refreshToken", type="string"),
     *          @SWG\Property(property="resourceId", type="string"),
     *          @SWG\Property(property="createdTime", type="integer"),
     *          @SWG\Property(property="updatedTime", type="integer"),
     *          @SWG\Property(property="expireTime", type="integer"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created token.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="tokens")
     */
    public function postAction(Token $token)
    {
        return $this->view($token, 201);
    }

    /**
     * Edit token
     *
     * @Put("/tokens/{resourceOwner}")
     * @param Token $token
     * @return \FOS\RestBundle\View\View
     * @ParamConverter("token", converter="request_body_converter", class="Model\Token\Token")
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"oauthToken"},
     *          @SWG\Property(property="oauthToken", type="string"),
     *          @SWG\Property(property="oauthTokenSecret", type="string"),
     *          @SWG\Property(property="refreshToken", type="string"),
     *          @SWG\Property(property="resourceId", type="string"),
     *          @SWG\Property(property="createdTime", type="integer"),
     *          @SWG\Property(property="updatedTime", type="integer"),
     *          @SWG\Property(property="expireTime", type="integer"),
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns edited token.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="tokens")
     */
    public function putAction(Token $token)
    {
        return $this->view($token);
    }
}