<?php

namespace Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class RequestBodyParamConverter implements ParamConverterInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param ContainerInterface $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(TokenStorageInterface $tokenStorage, ContainerInterface $container) {
        $this->tokenStorage = $tokenStorage;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $userId = $this->tokenStorage->getToken()->getUser()->getId();
        $data = $request->request->all();
        $data += $request->attributes->get('_route_params');

        $method = $request->getMethod();
        $managerClass = $configuration->getClass() . 'Manager';
        if (!$this->container->has($managerClass)) {
            throw new \Exception(sprintf('Manager %s does not exist', $managerClass));
        }

        $manager = $this->container->get($managerClass);

        switch ($method) {
            case 'GET':
                $object = $manager->getById($userId);
                break;
            case 'POST':
                $object = $manager->create($userId, $data);
                break;
            case 'PUT':
                $object = $manager->update($userId, $data);
                break;
            case 'DELETE':
                $object = $manager->delete($data, $userId);
                break;
            default:
                return false;
        }

        $request->attributes->set($configuration->getName(), $object);


        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration)
    {
        return null !== $configuration->getClass() && 'request_body_converter' === $configuration->getConverter();
    }
}
