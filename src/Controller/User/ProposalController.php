<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Service\ProposalService;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

class ProposalController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Create a proposal
     *
     * @Post("/proposals")
     * @param User $user
     * @param Request $request
     * @param ProposalService $proposalService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="description", type="string"),
     *          @SWG\Property(property="industry", type="string"),
     *          @SWG\Property(property="profession", type="string"),
     *          @SWG\Property(property="sport", type="string"),
     *          @SWG\Property(property="videogame", type="string"),
     *          @SWG\Property(property="hobby", type="string"),
     *          @SWG\Property(property="show", type="string"),
     *          @SWG\Property(property="restaurant", type="string"),
     *          @SWG\Property(property="plan", type="string"),
     *          @SWG\Property(property="availability", type="array[]"),
     *          example={ "name" = "work", "description" = "my first proposal", "industry" = "CS", "profession" = "web dev"},
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
     *     description="Returns created proposal",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="proposals")
     */
    public function createProposalAction(User $user, Request $request, ProposalService $proposalService)
    {
        $data = $request->request->all();

        $proposal = $proposalService->create($data, $user);

        return $this->view($proposal, 201);
    }

    /**
     * Get all proposals for a user
     *
     * @Get("/proposals")
     * @param User $user
     * @param ProposalService $proposalService
     * @return \FOS\RestBundle\View\View
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns all proposals",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="proposals")
     */
    public function getUserProposalsAction(User $user, ProposalService $proposalService)
    {
        $proposals = $proposalService->getByUser($user);

        return $this->view($proposals, 200);
    }
}