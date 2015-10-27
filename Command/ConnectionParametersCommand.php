<?php
/**
 * Tools to handle automatic dumps from sql databases in Symfony 2 projects.
 * [2015] Frank und Freunde GmbH
 * Christoph Martel <cm@fuf.de>
 */

namespace Fuf\BuilderBundle\Command;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Define sql dump command für symfony console.
 */
class ConnectionParametersCommand extends ContainerAwareCommand
{
    /**
     * Set up basic behavior of command.
     */
    protected function configure()
    {
        $this
            ->setName('fufx:db-conn')
            ->setDescription('Dump database connection parameters for script consumers.')
            ;
    }

    /**
     * Execute command and tell about it.
     * @param  InputInterface  $input  user input container
     * @param  OutputInterface $output output handler
     */
    protected function initialize(InputInterface $input, OutputInterface $output) 
    {
        $databaseName     = $this->getContainer()->getParameter('database_name');
        $databaseUser     = $this->getContainer()->getParameter('database_user');
        $databasePassword = $this->getContainer()->getParameter('database_password');
        $this->parameters = array($databaseName, $databaseUser, $databasePassword);
    }

    /**
     * Execute command and tell about it.
     * @param  InputInterface  $input  user input container
     * @param  OutputInterface $output output handler
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $returnValue = implode(';', $this->parameters);
        $output->write($returnValue);
    }
}
