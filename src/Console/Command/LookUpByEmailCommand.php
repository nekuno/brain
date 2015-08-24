<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Console\Command;

use Model\Entity\LookUpData;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Service\LookUp\LookUpFullContact;
use Service\LookUp\LookUpPeopleGraph;
use Doctrine\ORM\EntityManager;

class LookUpByEmailCommand extends ApplicationAwareCommand
{

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var OutputFormatterStyle
     */
    protected $successStyle;

    /**
     * @var OutputFormatterStyle
     */
    protected $messageStyle;

    /**
     * @var OutputFormatterStyle
     */
    protected $errorStyle;

    protected function configure()
    {
        $this->setName('look-up-by-email')
            ->setDescription('Look up user information using fullContact and peopleGraph')
            ->addOption('email', 'email', InputOption::VALUE_REQUIRED, 'Email to lookup', 'enredos@nekuno.com')
            ->addOption('force', 'force', InputOption::VALUE_NONE, 'Force lookup');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->successStyle = new OutputFormatterStyle('green', 'black', array('bold', 'blink'));
        $this->messageStyle = new OutputFormatterStyle('yellow', 'black', array('bold', 'blink'));
        $this->errorStyle = new OutputFormatterStyle('red', 'black', array('bold', 'blink'));
        $email = $input->getOption('email');
        $force = $input->getOption('force');
        $fullContactData = array();
        $peopleGraphData = array();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->displayError('Invalid email format');
            exit;
        }

        /** @var EntityManager $em */
        $em = $this->app['orm.ems']['mysql_brain'];
        $lookUpData = $em->getRepository('\Model\Entity\LookUpData')->findOneBy(array(
            'lookedUpType' => LookUpData::LOOKED_UP_BY_EMAIL,
            'lookedUpValue' => $email,
        ));

        if($force || ! $lookUpData || count($lookUpData->getSocialProfiles()) == 0) {

            /** @var $lookUpFullContact LookUpFullContact */
            $lookUpFullContact = $this->app['lookUp.fullContact.service'];
            /** @var $lookUpPeopleGraph LookUpPeopleGraph */
            $lookUpPeopleGraph = $this->app['lookUp.peopleGraph.service'];

            $lookUpData = $lookUpData ?: new LookUpData();
            $lookUpData->setEmail($email);
            $lookUpData->setLookedUpType(LookUpData::LOOKED_UP_BY_EMAIL);
            $lookUpData->setLookedUpValue($email);
            $em->persist($lookUpData);
            $em->flush();

            try {
                $this->displayTitle('Getting from FullContact');
                // TODO: Should pass $lookUpData->getId() as parameter, but doesn't work here
                $fullContactData = $lookUpFullContact->get(LookUpFullContact::EMAIL_TYPE, $email);
                $this->displayData($fullContactData->toArray());
            } catch (\Exception $e) {
                $this->displayError('<error>Error trying to look up: ' . $e->getMessage() . '</error>');
            }

            try {
                $this->displayTitle('Getting from PeopleGraph');
                // TODO: Should pass $lookUpData->getId() as parameter, but doesn't work here
                $peopleGraphData = $lookUpPeopleGraph->get(LookUpPeopleGraph::EMAIL_TYPE, $email);
                $this->displayData($peopleGraphData->toArray());
            } catch (\Exception $e) {
                $this->displayError('<error>Error trying to look up: ' . $e->getMessage() . '</error>');
            }

            $lookUpData = $lookUpFullContact->merge($lookUpData, $fullContactData);
            $lookUpData = $lookUpPeopleGraph->merge($lookUpData, $peopleGraphData);

            $em->persist($lookUpData);
            $em->flush();
        }

        $this->displayTitle('Display merged data');
        $this->displayData($lookUpData->toArray());

        $output->writeln('Done.');
    }

    private function displayData($data)
    {
        if(isset($data['socialProfiles']) && is_array($data['socialProfiles'])) {
            foreach($data['socialProfiles'] as $socialNetwork => $url) {

                $this->displayMessage('Social Network: ' . $socialNetwork);
                $this->displayMessage('Url: ' . $url);
            }
            $this->displaySuccess();
        }
        if(isset($data['name'])) {
            $this->displayMessage('Name: ' . $data['name']);
            $this->displaySuccess();
        }
        if(isset($data['email'])) {
            $this->displayMessage('Email: ' . $data['email']);
            $this->displaySuccess();
        }
        if(isset($data['gender'])) {
            $this->displayMessage('Gender: ' . $data['gender']);
            $this->displaySuccess();
        }
        if(isset($data['location'])) {
            $this->displayMessage('Location: ' . $data['location']);
            $this->displaySuccess();
        }
    }

    private function displayError($message)
    {
        $style = $this->errorStyle;
        $this->output->getFormatter()->setStyle('error', $style);
        $this->output->writeln('<error>' . $message . '</error>');
        $this->output->writeln('<error>FAIL</error>');
    }

    private function displayTitle($title)
    {
        $style = $this->successStyle;
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>' . $title . '</success>');
    }

    private function displayMessage($message)
    {
        $style = $this->messageStyle;
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>' . $message . '</success>');
    }

    private function displaySuccess()
    {
        $style = $this->successStyle;
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>SUCCESS</success>');
    }
}
