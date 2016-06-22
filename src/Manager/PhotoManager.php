<?php

namespace Manager;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Photo;
use Model\Neo4j\GraphManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PhotoManager
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var UserManager
     */
    protected $um;

    /**
     * @var string
     */
    protected $base;

    /**
     * @var string
     */
    protected $host;

    public function __construct(GraphManager $gm, UserManager $um, $base, $host)
    {
        $this->gm = $gm;
        $this->um = $um;
        $this->base = $base;
        $this->host = $host;
    }

    public function getAll($userId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)<-[:PHOTO_OF]-(i:Photo)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', $userId)
            ->returns('u', 'i')
            ->orderBy('i.createdAt DESC');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();

        foreach ($result as $row) {
            $return[] = $this->build($row);
        }

        return $return;
    }

    public function getById($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)<-[:PHOTO_OF]-(i:Photo)')
            ->where('id(i) = { id }')
            ->setParameter('id', (integer)$id)
            ->returns('u', 'i');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Photo not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function create($userId, $file)
    {

        $user = $this->um->getById($userId);

        // Validate
        $extension = $this->validate($file);

        // Save file
        $name = sha1(uniqid($user->getUsernameCanonical() . '_' . time(), true)) . '.' . $extension;
        $path = 'uploads/gallery/' . md5($user->getId()) . '/' . $name;
        file_put_contents($this->base . $path, $file);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { id }})')
            ->with('u')
            ->create('(u)<-[:PHOTO_OF]-(i:Photo)')
            ->set('i.createdAt = { createdAt }', 'i.path = { path }')
            ->setParameters(
                array(
                    'id' => (int)$userId,
                    'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'path' => $path,
                )
            )
            ->returns('u', 'i');

        $result = $qb->getQuery()->getResultSet();

        if (count($result) < 1) {
            throw new \Exception('Could not create Photo');
        }

        $row = $result->current();

        return $this->build($row);

    }

    public function remove($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)<-[r:PHOTO_OF]-(i:Photo)')
            ->where('id(i)= { id }')
            ->setParameter('id', (integer)$id)
            ->delete('r', 'i')
            ->returns('u');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Photo not found');
        }

        return true;

    }

    public function validate($file)
    {

        $max = 1900000;
        if (strlen($file) > $max) {
            throw new ValidationException(array('photo' => array(sprintf('Max "%s" bytes file size exceed', $max))));
        }

        $extension = null;

        if (!$finfo = new \finfo(FILEINFO_MIME_TYPE)) {
            throw new ValidationException(array('photo' => array('Unable to guess file mime type')));
        }

        $mimeType = $finfo->buffer($file);

        $validTypes = array(
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
        );

        if (!isset($validTypes[$mimeType])) {
            throw new ValidationException(array('photo' => array(sprintf('Invalid mime type, possibles values are "%s".', implode('", "', array_keys($validTypes))))));
        }

        return $validTypes[$mimeType];
    }

    /**
     * @param Row $row
     * @return Photo
     */
    protected function build(Row $row)
    {

        /* @var $node Node */
        $node = $row->offsetGet('i');

        /* @var $userNode Node */
        $userNode = $row->offsetGet('u');
        $user = $this->um->getById($userNode->getProperty('qnoow_id'));

        $photo = new Photo($this->base, $this->host);
        $photo->setId($node->getId());
        $photo->setCreatedAt(new \DateTime($node->getProperty('createdAt')));
        $photo->setPath($node->getProperty('path'));
        $photo->setUser($user);

        return $photo;
    }

}