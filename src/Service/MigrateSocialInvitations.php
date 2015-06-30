<?php

/** @author Manolo Salsas (manolez@gmail.com) **/

namespace Service;

use Doctrine\DBAL\Connection;
use Model\Neo4j\GraphManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrateSocialInvitations
 * @package Service
 */
class MigrateSocialInvitations
{
    protected $sm;
    protected $gm;
    protected $adminDomain;

    public function __construct(GraphManager $gm, Connection $sm, $adminDomain)
    {
        $this->gm = $gm;
        $this->sm = $sm;
        $this->adminDomain = $adminDomain;
    }

    public function migrateInvitations(OutputInterface $output)
    {
        $qb = $this->sm->createQueryBuilder('invitations')
            ->select('*')
            ->from('invitations');

        $invitations = $qb->execute()->fetchAll();

        $output->writeln(count($invitations) . ' invitations found');

        foreach($invitations as $invitation)
        {
            if($this->migrateInvitation($invitation, $output)) {
                $output->writeln('Invitation ' . $invitation['id'] . ' migrated');
            } else {
                $output->writeln('Error migrating invitation ' . $invitation['id']);
            }
        }
    }

    protected function migrateInvitation(array $invitation, OutputInterface $output)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->create('(inv:Invitation)')
            ->set('inv.token = { token }')
            ->set('inv.available = { available }')
            ->set('inv.consumed = { consumed }')
            ->set('inv.email = { email }')
            ->set('inv.expiresAt = { expiresAt }')
            ->set('inv.createdAt = { createdAt }')
            ->set('inv.htmlText = { htmlText }')
            ->set('inv.orientationRequired = { orientationRequired }')
            ->set('inv.slogan = { slogan }');

        $imageName = '';
        $imagePath = '';
        if($invitation['image_id'] && $imagePath = $this->getImagePath($invitation['image_id'])) {
            $imageName = substr($imagePath, strpos($imagePath, 'uploads/invitation-gallery/') + strlen('uploads/invitation-gallery/'));
            if (!is_dir('../admin/web/uploads')) {
                mkdir('../admin/web/uploads');
            }
            if (!is_dir('../admin/web/uploads/invitation-gallery')) {
                mkdir('../admin/web/uploads/invitation-gallery');
            }
            rename('../social/web/uploads/invitation-gallery/' . $imageName, '../admin/web/uploads/invitation-gallery/' . $imageName);
            $qb->set('inv.image_url = { image_url }');
        }

        if($invitation['groupId']) {
            $qb->with('inv')
                ->match('(g:Group)')
                ->where('id(g) = { groupId }')
                ->createUnique('(inv)-[:HAS_GROUP]->(g)');
        }

        if($invitation['user_id']) {
            $qb->with('inv')
                ->match('(u:User)')
                ->where('u.qnoow_id = { user_id }')
                ->createUnique('(u)-[:CREATED_INVITATION]->(inv)');
        }

        if($consumedUsersId = $this->getConsumedUsersId($invitation)) {
            foreach($consumedUsersId as $consumedUserId) {
                $qb->with('inv')
                    ->match('(cu:User)')
                    ->where('cu.qnoow_id = ' . (int)$consumedUserId['user_id'])
                    ->createUnique('(cu)-[:CONSUMED_INVITATION]->(inv)');
            }

        }

        $qb->setParameters(array(
            'token' => $invitation['token'],
            'available' => (int)$invitation['available'],
            'consumed' => (int)$invitation['consumed'],
            'email' => $invitation['email'],
            'expiresAt' => $invitation['expiresAt'] ? strtotime($invitation['expiresAt']) : null,
            'createdAt' => strtotime($invitation['createdAt']),
            'htmlText' => $invitation['htmlText'],
            'orientationRequired' => (boolean)$invitation['orientationRequired'],
            'slogan' => $invitation['slogan'],
            'image_url' => $imagePath ? $this->adminDomain . 'uploads/invitation-gallery/' . $imageName : null,
            'groupId' => $invitation['groupId']? (int)$invitation['groupId'] : null,
            'user_id' => $invitation['user_id'] ? (int)$invitation['user_id'] : null,
        ))
            ->returns('inv');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            return true;
        }

        return null;
    }

    protected function getImagePath($imageId)
    {
        $qb = $this->sm->createQueryBuilder('invitation_images')
            ->select('invitation_images.image_path')
            ->from('invitation_images')
            ->where('invitation_images.id = :imageId')
            ->setParameter('imageId', $imageId);

        $imagePath = $qb->execute()->fetchColumn();

        return $imagePath ?: null;
    }

    protected function getConsumedUsersId(array $invitation)
    {
        $qb = $this->sm->createQueryBuilder('invitations_users')
            ->select('invitations_users.user_id')
            ->from('invitations_users')
            ->where('invitations_users.invitation_id = :invitationId')
            ->setParameter('invitationId', $invitation['id']);

        $usersId = $qb->execute()->fetchAll();

        return !empty($usersId) ? $usersId : null;
    }
}