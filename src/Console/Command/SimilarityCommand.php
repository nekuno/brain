<?php

namespace Console\Command;

use Model\User\Similarity\SimilarityModel;
use Model\UserModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SimilarityCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('similarity:calculate')
            ->setDescription('Calculate the similarity of two users.')
            ->addArgument(
                'userA',
                InputArgument::OPTIONAL,
                'id of the first user?'
            )
            ->addArgument(
                'userB',
                InputArgument::OPTIONAL,
                'id of the second user?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $model SimilarityModel */
        $model = $this->app['users.similarity.model'];

        $userA = $input->getArgument('userA');
        $userB = $input->getArgument('userB');

        $combinations = array(
            array(
                0 => $userA,
                1 => $userB
            )
        );
        if (null === $userA || null === $userB) {
            /* @var $userModel UserModel */
            $userModel = $this->app['users.model'];
            $combinations = $userModel->getAllCombinations();
        }

        try {
            foreach ($combinations AS $users) {
                $userA = $users[0];
                $userB = $users[1];

                $similarity = $model->getSimilarity($userA, $userB);
            }

            if (count($combinations) == 1) {
                $output->writeln('');

                $table = $this->getHelper('table');
                $table
                    ->setHeaders(array('Type', 'Value', 'Last Updated'))
                    ->setRows(array(
                        array('Questions', $similarity['questions'], $similarity['questionsUpdated']),
                        array('Interests', $similarity['interests'], $similarity['interestsUpdated']),
                        array('Similarity', $similarity['similarity'], $similarity['similarityUpdated']),
                    ))
                ;
                $table->render($output);
            }

        } catch (\Exception $e) {
            $output->writeln(
                'Error trying to recalculate similarity with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Done.');

    }
}
