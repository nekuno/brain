<?php

namespace Console\Command;

use Model\Neo4j\Fixtures;

use Psr\Log\LogLevel;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jFixturesCommand extends ApplicationAwareCommand
{

    protected function configure()
    {
        $this->setName('neo4j:fixtures')
            ->setDescription('Load neo4j database fixtures')
            ->addArgument('scenario', InputArgument::REQUIRED, 'The id of the scenario: 1, 2, 3, 4, 5');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $scenario = $input->getArgument('scenario');

        $fixtures = new Fixtures($this->app['neo4j.graph_manager'], $this->app['users.model'], $this->app['links.model'], $this->app['questionnaire.questions.model'], $this->app['users.answers.model'], $this->getScenario($scenario));

        $logger = new ConsoleLogger($output);
        $fixtures->setLogger($logger);

        $output->writeln('Loading fixtures');

        try {
            $fixtures->load();
        } catch (\Exception $e) {
            $output->writeln(
                'Error loading neo4j fixtures with message: ' . $e->getMessage()
            );

            return;
        }

        $output->writeln('Fixtures created');
    }

    protected function getScenario($scenario)
    {
        $scenarios = array(
            1 => array(
                'likes' => array(
                    array(
                        'user' => 1,
                        'linkFrom' => 1,
                        'linkTo' => 19,
                    ),
                    array(
                        'user' => 2,
                        'linkFrom' => 1,
                        'linkTo' => 18,
                    ),
                    array(
                        'user' => 3,
                        'linkFrom' => 1,
                        'linkTo' => 5,
                    ),
                    array(
                        'user' => 4,
                        'linkFrom' => 6,
                        'linkTo' => 10,
                    ),
                    array(
                        'user' => 5,
                        'linkFrom' => 1,
                        'linkTo' => 5,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 1,
                        'linkTo' => 1,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 3,
                        'linkTo' => 3,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 5,
                        'linkTo' => 5,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 7,
                        'linkTo' => 7,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 9,
                        'linkTo' => 9,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 11,
                        'linkTo' => 11,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 13,
                        'linkTo' => 13,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 15,
                        'linkTo' => 15,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 1,
                        'linkTo' => 1,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 3,
                        'linkTo' => 3,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 5,
                        'linkTo' => 5,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 7,
                        'linkTo' => 7,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 8,
                        'linkTo' => 8,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 10,
                        'linkTo' => 10,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 12,
                        'linkTo' => 12,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 14,
                        'linkTo' => 14,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 16,
                        'linkTo' => 16,
                    ),
                    array(
                        'user' => 8,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 8,
                        'linkFrom' => 6,
                        'linkTo' => 14,
                    ),
                    array(
                        'user' => 8,
                        'linkFrom' => 19,
                        'linkTo' => 19,
                    ),
                ),
                'answers' => array(
                    array(
                        'user' => 1,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 3,
                    ),
                    array(
                        'user' => 2,
                        'answer' => 1,
                        'questionFrom' => 2,
                        'questionTo' => 3,
                    ),
                    array(
                        'user' => 3,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 3,
                    ),
                    array(
                        'user' => 4,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 2,
                    ),
                    array(
                        'user' => 4,
                        'answer' => 3,
                        'questionFrom' => 3,
                        'questionTo' => 4,
                    ),
                    array(
                        'user' => 5,
                        'answer' => 2,
                        'questionFrom' => 1,
                        'questionTo' => 1,
                    ),
                    array(
                        'user' => 5,
                        'answer' => 1,
                        'questionFrom' => 2,
                        'questionTo' => 4,
                    ),
                    array(
                        'user' => 6,
                        'answer' => 2,
                        'questionFrom' => 1,
                        'questionTo' => 4,
                    ),
                    array(
                        'user' => 7,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 4,
                    ),
                ),
            ),
            2 => array(
                'likes' => array(
                    array(
                        'user' => 1,
                        'linkFrom' => 1,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 2,
                        'linkFrom' => 1,
                        'linkTo' => 1800,
                    ),
                    array(
                        'user' => 3,
                        'linkFrom' => 1,
                        'linkTo' => 1800,
                    ),
                    array(
                        'user' => 4,
                        'linkFrom' => 1,
                        'linkTo' => 1800,
                    ),
                    array(
                        'user' => 5,
                        'linkFrom' => 1,
                        'linkTo' => 1800,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 1,
                        'linkTo' => 1900,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 1,
                        'linkTo' => 1900,
                    ),
                    array(
                        'user' => 8,
                        'linkFrom' => 1,
                        'linkTo' => 1900,
                    ),
                    array(
                        'user' => 9,
                        'linkFrom' => 1,
                        'linkTo' => 1900,
                    ),
                    array(
                        'user' => 10,
                        'linkFrom' => 1,
                        'linkTo' => 1900,
                    ),
                    array(
                        'user' => 11,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 12,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 13,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 14,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 15,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 16,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 17,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 18,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 19,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 20,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 21,
                        'linkFrom' => 1,
                        'linkTo' => 200,
                    ),
                    array(
                        'user' => 22,
                        'linkFrom' => 1,
                        'linkTo' => 200,
                    ),
                    array(
                        'user' => 23,
                        'linkFrom' => 1,
                        'linkTo' => 200,
                    ),
                    array(
                        'user' => 24,
                        'linkFrom' => 1,
                        'linkTo' => 200,
                    ),
                    array(
                        'user' => 25,
                        'linkFrom' => 1,
                        'linkTo' => 200,
                    ),
                    array(
                        'user' => 26,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 26,
                        'linkFrom' => 1800,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 27,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 27,
                        'linkFrom' => 1800,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 28,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 28,
                        'linkFrom' => 1800,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 29,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 29,
                        'linkFrom' => 1800,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 30,
                        'linkFrom' => 1,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 30,
                        'linkFrom' => 1800,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 31,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 31,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 32,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 32,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 33,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 33,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 34,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 34,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 35,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 35,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 36,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 36,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 37,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 37,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 38,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 38,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 39,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 39,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 40,
                        'linkFrom' => 1,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 40,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                ),
                'answers' => array(
                    array(
                        'user' => 1,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 2,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 35,
                    ),
                    array(
                        'user' => 2,
                        'answer' => 2,
                        'questionFrom' => 36,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 3,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 35,
                    ),
                    array(
                        'user' => 3,
                        'answer' => 2,
                        'questionFrom' => 36,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 4,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 35,
                    ),
                    array(
                        'user' => 4,
                        'answer' => 2,
                        'questionFrom' => 36,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 5,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 35,
                    ),
                    array(
                        'user' => 5,
                        'answer' => 2,
                        'questionFrom' => 36,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 6,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 6,
                        'answer' => 3,
                        'questionFrom' => 31,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 7,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 7,
                        'answer' => 3,
                        'questionFrom' => 31,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 8,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 8,
                        'answer' => 3,
                        'questionFrom' => 31,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 9,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 9,
                        'answer' => 3,
                        'questionFrom' => 31,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 10,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 10,
                        'answer' => 3,
                        'questionFrom' => 31,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 11,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 20,
                    ),
                    array(
                        'user' => 11,
                        'answer' => 2,
                        'questionFrom' => 21,
                        'questionTo' => 35,
                    ),
                    array(
                        'user' => 11,
                        'answer' => 3,
                        'questionFrom' => 36,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 12,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 20,
                    ),
                    array(
                        'user' => 12,
                        'answer' => 2,
                        'questionFrom' => 21,
                        'questionTo' => 35,
                    ),
                    array(
                        'user' => 12,
                        'answer' => 3,
                        'questionFrom' => 36,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 13,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 20,
                    ),
                    array(
                        'user' => 13,
                        'answer' => 2,
                        'questionFrom' => 21,
                        'questionTo' => 35,
                    ),
                    array(
                        'user' => 13,
                        'answer' => 3,
                        'questionFrom' => 36,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 14,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 20,
                    ),
                    array(
                        'user' => 14,
                        'answer' => 2,
                        'questionFrom' => 21,
                        'questionTo' => 35,
                    ),
                    array(
                        'user' => 14,
                        'answer' => 3,
                        'questionFrom' => 36,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 15,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 20,
                    ),
                    array(
                        'user' => 15,
                        'answer' => 2,
                        'questionFrom' => 21,
                        'questionTo' => 35,
                    ),
                    array(
                        'user' => 15,
                        'answer' => 3,
                        'questionFrom' => 36,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 16,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 16,
                        'answer' => 1,
                        'questionFrom' => 30,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 17,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 17,
                        'answer' => 1,
                        'questionFrom' => 30,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 18,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 18,
                        'answer' => 1,
                        'questionFrom' => 30,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 19,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 19,
                        'answer' => 1,
                        'questionFrom' => 30,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 20,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 20,
                        'answer' => 1,
                        'questionFrom' => 30,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 21,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 22,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 23,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 24,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 25,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 26,
                        'answer' => 1,
                        'questionFrom' => 6,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 27,
                        'answer' => 1,
                        'questionFrom' => 6,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 28,
                        'answer' => 1,
                        'questionFrom' => 6,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 29,
                        'answer' => 1,
                        'questionFrom' => 6,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 30,
                        'answer' => 1,
                        'questionFrom' => 6,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 31,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 31,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 32,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 32,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 33,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 33,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 34,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 34,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 35,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 35,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 36,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 36,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 37,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 37,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 38,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 38,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 39,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 39,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 40,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 38,
                    ),
                    array(
                        'user' => 40,
                        'answer' => 3,
                        'questionFrom' => 39,
                        'questionTo' => 40,
                    ),
                ),
            ),
            3 => array(
                'likes' => array(
                    // Grupo 1
                    array(
                        'user' => 1,
                        'linkFrom' => 1,
                        'linkTo' => 500,
                    ),
                    array(
                        'user' => 2,
                        'linkFrom' => 1,
                        'linkTo' => 500,
                    ),
                    array(
                        'user' => 3,
                        'linkFrom' => 1,
                        'linkTo' => 600,
                    ),
                    array(
                        'user' => 4,
                        'linkFrom' => 1,
                        'linkTo' => 400,
                    ),
                    array(
                        'user' => 5,
                        'linkFrom' => 1,
                        'linkTo' => 600,
                    ),
                    // Grupo 2
                    array(
                        'user' => 6,
                        'linkFrom' => 500,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 400,
                        'linkTo' => 1100,
                    ),
                    array(
                        'user' => 8,
                        'linkFrom' => 500,
                        'linkTo' => 1100,
                    ),
                    array(
                        'user' => 9,
                        'linkFrom' => 600,
                        'linkTo' => 1200,
                    ),
                    array(
                        'user' => 10,
                        'linkFrom' => 400,
                        'linkTo' => 800,
                    ),
                    // Grupo 3
                    array(
                        'user' => 11,
                        'linkFrom' => 900,
                        'linkTo' => 1400,
                    ),
                    array(
                        'user' => 12,
                        'linkFrom' => 1000,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 13,
                        'linkFrom' => 1100,
                        'linkTo' => 1600,
                    ),
                    array(
                        'user' => 14,
                        'linkFrom' => 1000,
                        'linkTo' => 1500,
                    ),
                    array(
                        'user' => 15,
                        'linkFrom' => 900,
                        'linkTo' => 1600,
                    ),
                    // Grupo 4
                    array(
                        'user' => 16,
                        'linkFrom' => 1500,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 17,
                        'linkFrom' => 1600,
                        'linkTo' => 2000,
                    ),
                    array(
                        'user' => 18,
                        'linkFrom' => 1400,
                        'linkTo' => 1900,
                    ),
                    array(
                        'user' => 19,
                        'linkFrom' => 1500,
                        'linkTo' => 1900,
                    ),
                    array(
                        'user' => 20,
                        'linkFrom' => 1300,
                        'linkTo' => 1800,
                    ),
                ),
                'answers' => array(
                    // Grupo 1
                    array(
                        'user' => 1,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 2,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 9,
                    ),
                    array(
                        'user' => 3,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 4,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 10,
                    ),
                    array(
                        'user' => 5,
                        'answer' => 1,
                        'questionFrom' => 3,
                        'questionTo' => 12,
                    ),
                    // Grupo 2
                    array(
                        'user' => 6,
                        'answer' => 1,
                        'questionFrom' => 11,
                        'questionTo' => 20,
                    ),
                    array(
                        'user' => 7,
                        'answer' => 1,
                        'questionFrom' => 12,
                        'questionTo' => 20,
                    ),
                    array(
                        'user' => 8,
                        'answer' => 1,
                        'questionFrom' => 11,
                        'questionTo' => 20,
                    ),
                    array(
                        'user' => 9,
                        'answer' => 1,
                        'questionFrom' => 13,
                        'questionTo' => 19,
                    ),
                    array(
                        'user' => 10,
                        'answer' => 1,
                        'questionFrom' => 13,
                        'questionTo' => 20,
                    ),
                    // Grupo 3
                    array(
                        'user' => 11,
                        'answer' => 1,
                        'questionFrom' => 21,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 12,
                        'answer' => 1,
                        'questionFrom' => 19,
                        'questionTo' => 28,
                    ),
                    array(
                        'user' => 13,
                        'answer' => 1,
                        'questionFrom' => 22,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 14,
                        'answer' => 1,
                        'questionFrom' => 21,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 15,
                        'answer' => 1,
                        'questionFrom' => 18,
                        'questionTo' => 32,
                    ),
                    // Grupo 4
                    array(
                        'user' => 16,
                        'answer' => 1,
                        'questionFrom' => 29,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 17,
                        'answer' => 1,
                        'questionFrom' => 30,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 18,
                        'answer' => 1,
                        'questionFrom' => 31,
                        'questionTo' => 39,
                    ),
                    array(
                        'user' => 19,
                        'answer' => 1,
                        'questionFrom' => 32,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 20,
                        'answer' => 1,
                        'questionFrom' => 31,
                        'questionTo' => 40,
                    ),
                ),
            ),
            4 => array(
                'likes' => array(
                    // Grupo 1: 15 Usuarios, 900 contenidos
                    array(
                        'user' => 1,
                        'linkFrom' => 1,
                        'linkTo' => 700,
                    ),
                    array(
                        'user' => 1,
                        'linkFrom' => 901,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 2,
                        'linkFrom' => 1,
                        'linkTo' => 700,
                    ),
                    array(
                        'user' => 2,
                        'linkFrom' => 901,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 3,
                        'linkFrom' => 1,
                        'linkTo' => 700,
                    ),
                    array(
                        'user' => 4,
                        'linkFrom' => 1,
                        'linkTo' => 700,
                    ),
                    array(
                        'user' => 5,
                        'linkFrom' => 1,
                        'linkTo' => 700,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 1,
                        'linkTo' => 900,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 1,
                        'linkTo' => 900,
                    ),
                    array(
                        'user' => 8,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 9,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 10,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 11,
                        'linkFrom' => 1,
                        'linkTo' => 800,
                    ),
                    array(
                        'user' => 11,
                        'linkFrom' => 900,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 12,
                        'linkFrom' => 1,
                        'linkTo' => 800,
                    ),
                    array(
                        'user' => 12,
                        'linkFrom' => 1100,
                        'linkTo' => 1200,
                    ),
                    array(
                        'user' => 13,
                        'linkFrom' => 1,
                        'linkTo' => 800,
                    ),
                    array(
                        'user' => 14,
                        'linkFrom' => 1,
                        'linkTo' => 800,
                    ),
                    array(
                        'user' => 15,
                        'linkFrom' => 1,
                        'linkTo' => 800,
                    ),
                    // Grupo 2: 5 usuarios, 300 contenidos
                    array(
                        'user' => 16,
                        'linkFrom' => 900,
                        'linkTo' => 1200,
                    ),
                    array(
                        'user' => 17,
                        'linkFrom' => 800,
                        'linkTo' => 1200,
                    ),
                    array(
                        'user' => 18,
                        'linkFrom' => 900,
                        'linkTo' => 1200,
                    ),
                    array(
                        'user' => 19,
                        'linkFrom' => 900,
                        'linkTo' => 1200,
                    ),
                    array(
                        'user' => 20,
                        'linkFrom' => 1,
                        'linkTo' => 100,
                    ),
                    array(
                        'user' => 20,
                        'linkFrom' => 900,
                        'linkTo' => 1200,
                    ),
                ),
                'answers' => array(
                    // Grupo 1: 15 usuarios, 30 preguntas básicas respondidas
                    array(
                        'user' => 1,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 2,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 3,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 4,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 25,
                    ),
                    array(
                        'user' => 5,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 6,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 32,
                    ),
                    array(
                        'user' => 7,
                        'answer' => 1,
                        'questionFrom' => 3,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 8,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 9,
                        'answer' => 1,
                        'questionFrom' => 2,
                        'questionTo' => 29,
                    ),
                    array(
                        'user' => 10,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 11,
                        'answer' => 1,
                        'questionFrom' => 5,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 12,
                        'answer' => 1,
                        'questionFrom' => 2,
                        'questionTo' => 30,
                    ),
                    array(
                        'user' => 13,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 33,
                    ),
                    array(
                        'user' => 14,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 32,
                    ),
                    array(
                        'user' => 15,
                        'answer' => 1,
                        'questionFrom' => 3,
                        'questionTo' => 35,
                    ),
                    // Grupo 2: 5 usuarios, 10 preguntas básicas respondidas
                    array(
                        'user' => 15,
                        'answer' => 1,
                        'questionFrom' => 31,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 15,
                        'answer' => 1,
                        'questionFrom' => 32,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 15,
                        'answer' => 1,
                        'questionFrom' => 31,
                        'questionTo' => 39,
                    ),
                    array(
                        'user' => 15,
                        'answer' => 1,
                        'questionFrom' => 31,
                        'questionTo' => 40,
                    ),
                    array(
                        'user' => 15,
                        'answer' => 1,
                        'questionFrom' => 31,
                        'questionTo' => 40,
                    ),
                ),
            ),
            5 => array(
                'likes' => array(
                    array(
                        'user' => 1,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 2,
                        'linkFrom' => 1,
                        'linkTo' => 1000,
                    ),
                    array(
                        'user' => 3,
                        'linkFrom' => 1,
                        'linkTo' => 100,
                    ),
                    array(
                        'user' => 4,
                        'linkFrom' => 50,
                        'linkTo' => 150,
                    ),
                    array(
                        'user' => 5,
                        'linkFrom' => 1,
                        'linkTo' => 15,
                    ),
                    array(
                        'user' => 6,
                        'linkFrom' => 10,
                        'linkTo' => 25,
                    ),
                    array(
                        'user' => 7,
                        'linkFrom' => 1101,
                        'linkTo' => 1115,
                    ),
                    array(
                        'user' => 8,
                        'linkFrom' => 1110,
                        'linkTo' => 1125,
                    ),
                    array(
                        'user' => 9,
                        'linkFrom' => 1501,
                        'linkTo' => 1511,
                    ),
                    array(
                        'user' => 10,
                        'linkFrom' => 1507,
                        'linkTo' => 1515,
                    ),
                ),
                'answers' => array(
                    array(
                        'user' => 1,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 120,
                    ),
                    array(
                        'user' => 1,
                        'answer' => 1,
                        'questionFrom' => 121,
                        'questionTo' => 180,
                    ),
                    array(
                        'user' => 1,
                        'answer' => 1,
                        'questionFrom' => 181,
                        'questionTo' => 190,
                    ),
                    array(
                        'user' => 2,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 120,
                    ),
                    array(
                        'user' => 2,
                        'answer' => 2,
                        'questionFrom' => 121,
                        'questionTo' => 180,
                    ),
                    array(
                        'user' => 2,
                        'answer' => 1,
                        'questionFrom' => 191,
                        'questionTo' => 200,
                    ),
                    // 18 common questions with same answer
                    array(
                        'user' => 3,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 18,
                    ),
                    array(
                        'user' => 4,
                        'answer' => 1,
                        'questionFrom' => 1,
                        'questionTo' => 18,
                    ),
                    // 52 common questions
                    array(
                        'user' => 3,
                        'answer' => 1,
                        'questionFrom' => 19,
                        'questionTo' => 52,
                    ),
                    array(
                        'user' => 4,
                        'answer' => 2,
                        'questionFrom' => 19,
                        'questionTo' => 52,
                    ),
                    // 120 and 78 questions in total
                    array(
                        'user' => 3,
                        'answer' => 1,
                        'questionFrom' => 53,
                        'questionTo' => 120,
                    ),
                    array(
                        'user' => 4,
                        'answer' => 2,
                        'questionFrom' => 121,
                        'questionTo' => 127,
                    ),
                ),
            ),
        );

        if (!isset($scenarios[$scenario])) {
            throw new \InvalidArgumentException(sprintf('Invalid scenario id %s', $scenario));
        }

        return $scenarios[$scenario];
    }
}