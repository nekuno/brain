<?php

namespace Console\Command;

use Model\User\Similarity\SimilarityModel;
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
                InputArgument::REQUIRED,
                'id of the first user?'
            )
            ->addArgument(
                'userB',
                InputArgument::REQUIRED,
                'id of the second user?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $model SimilarityModel */
        $model = $this->app['users.similarity.model'];

        $userA = $input->getArgument('userA');
        $userB = $input->getArgument('userB');

        try {
            $similarity = $model->getSimilarity($userA, $userB);

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

        } catch (\Exception $e) {
            $output->writeln(
                'Error trying to recalculate similarity with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Done.');

    }
}
