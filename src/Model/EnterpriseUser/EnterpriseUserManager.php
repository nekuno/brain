<?php

namespace Model\EnterpriseUser;

use Everyman\Neo4j\Query\Row;
use Model\Exception\ErrorList;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class EnterpriseUserManager
{

    /**
     * @var GraphManager
     */
    protected $gm;

    public function __construct(GraphManager $gm)
    {
        $this->gm = $gm;
    }

    public function getById($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(eu:EnterpriseUser {admin_id: { id }})')
            ->setParameter('id', (integer)$id)
            ->returns('eu');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException(sprintf('EnterpriseUser "%d" not found', $id));
        }

        return $this->parseRow($result->current());

    }

    public function create(array $data)
    {
        $this->validate($data);

        if (!isset($data['email'])) {
            $data['email'] = '';
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->merge('(eu:EnterpriseUser {admin_id: { admin_id }, username: { username }, email: { email }})')
            ->setParameters(
                array(
                    'admin_id' => (integer)$data['admin_id'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                )
            )
            ->returns('eu');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->parseRow($row);
    }

    public function update(array $data)
    {
        $this->validate($data);

        if (!isset($data['email'])) {
            $data['email'] = '';
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(eu:EnterpriseUser {admin_id: { admin_id }})')
            ->set('username = { username }', 'email = { email }')
            ->setParameters(
                array(
                    'admin_id' => (integer)$data['id'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                )
            )
            ->returns('eu');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->parseRow($row);
    }

    public function remove($id = null)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(eu:EnterpriseUser {admin_id: { id }})')
            ->delete('eu')
            ->setParameter('id', (integer)$id);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->parseResultSet($result);
    }

    public function validate(array $data)
    {
        $errorList = new ErrorList();

        if (!isset($data['admin_id']) || !$data['admin_id']) {
            $errorList->addError('admin_id', '"admin_id" is required');
        } elseif ((string)(int)$data['admin_id'] !== (string)$data['admin_id']) {
            $errorList->addError('admin_id', '"admin_id" must be an integer');
        }

        if (!isset($data['username']) || !$data['username']) {
            $errorList->addError('username', '"username" is required');
        } elseif (!is_string($data['username'])) {
            $errorList->addError('username', '"username" must be string');
        }

        if (!isset($data['email']) || !$data['email']) {
            $errorList->addError('email', '"email" is required');
        } elseif (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errorList->addError('email', '"email" must be a valid email');
        }

        if ($errorList->hasErrors()) {
            throw new ValidationException($errorList);
        }
    }

    public function exists($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(eu:EnterpriseUser {admin_id: { id }})')
            ->setParameter('id', (integer)$id)
            ->returns('eu');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() < 1) {
            return false;
        }

        return true;
    }

    /**
     * @param $resultSet
     * @return array
     */
    private function parseResultSet($resultSet)
    {
        $users = array();

        foreach ($resultSet as $row) {
            $users[] = $this->parseRow($row);
        }

        return $users;
    }

    private function parseRow(Row $row)
    {
        return array(
            'admin_id' => $row['eu']->getProperty('admin_id'),
            'username' => $row['eu']->getProperty('username'),
            'email' => $row['eu']->getProperty('email'),
        );
    }
}
