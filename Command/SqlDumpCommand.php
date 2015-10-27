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
class SqlDumpCommand extends ContainerAwareCommand
{
    /**
     * Path to mysqldump executable
     * @var string
     */
    protected static $MYSQLDUMPCMD;

    /**
     * Path to gzip executable.
     * @var string
     */
    protected static $COMPRESSCMD;

    /**
     * Set up basic behavior of command.
     */
    protected function configure()
    {
        $this
            ->setName('fufx:sql-dump')
            ->setDescription('Dump current database to sql.')
            ->addOption(
                'compress',
                null,
                InputOption::VALUE_NONE,
                'If set, the output sql will be compressed.'
            )
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Give some debug output.'
            )
            ->addOption(
                'skip',
                null,
                InputOption::VALUE_REQUIRED,
                'Skip the specified tables for the dump.'
            )
            ;
    }

    /**
     * Set basic values for command from parameters.yml.
     * @param  InputInterface  $input  user input container
     * @param  OutputInterface $output output handler
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        self::$MYSQLDUMPCMD   = exec('which mysqldump');
        self::$COMPRESSCMD    = exec('which gzip');
        $dumpDate             = date('Ymd_Hms');
        $defaultOptions       = array('--dump-date',
                                      '--comments',
                                      '--add-drop-table',
                                      '--add-locks',
                                      '--create-options',
                                      '--quick',
                                      );

        $databaseHost         = $this->getContainer()->getParameter('database_host');
        $databasePort         = $this->getContainer()->getParameter('database_port');
        $this->databaseName   = $this->getContainer()->getParameter('database_name');
        $databaseUser         = $this->getContainer()->getParameter('database_user');
        $databasePassword     = $this->getContainer()->getParameter('database_password');

        if (!$this->databaseName) {
            throw new InvalidArgumentException('Cannot find database name in your parameters.yml.');
        }

        $databaseHostParameter      = $databaseHost     ? sprintf('--host=%s', $databaseHost) : false;
        $databasePortParameter      = $databasePort     ? sprintf('--port=%s', $databasePort) : false;
        $databaseUserParameter      = $databaseUser     ? sprintf('--user=%s', $databaseUser) : false;
        $databasePasswordParameter  = $databasePassword ? sprintf('--password=%s', $databasePassword) : false;

        $this->resultFileName       = sprintf(
            '%s_%s.sql',
            $this->getContainer()->getParameter('database_name'),
            $dumpDate
        );
        $resultFileParameter        = sprintf('--result-file=%s', $this->resultFileName);
        $this->debugOptions         = array('--debug-check', '--debug-info', '--verbose');

        if ($databaseHostParameter) {
            $cmdOptions[] = $databaseHostParameter;
        }
        if ($databasePortParameter) {
            $cmdOptions[] = $databasePortParameter;
        }
        if ($databaseUserParameter) {
            $cmdOptions[] = $databaseUserParameter;
        }
        if ($databasePasswordParameter) {
            $cmdOptions[] = $databasePasswordParameter;
        }
        $cmdOptions[] = $resultFileParameter;


        array($databaseHostParameter,
              $databasePortParameter,
              $databaseUserParameter,
              $databasePasswordParameter,
              $resultFileParameter,
              );

        $this->options = array_merge($cmdOptions, $defaultOptions);

        $this->dumpCmd = new ProcessBuilder();
        $this->dumpCmd->setPrefix(self::$MYSQLDUMPCMD);
        $this->dumpCmd->setArguments($this->options);

        $this->zipCmd = new ProcessBuilder();
        $this->zipCmd->setPrefix(self::$COMPRESSCMD);
    }

    /**
     * Override base settings if necessary.
     * @param  InputInterface  $input  user input container
     * @param  OutputInterface $output output handler
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if ($skippedTablesString = $input->getOption('skip')) {
            $skippedTables = explode(',', $skippedTablesString);
            foreach ($skippedTables as $key => $table) {
                $skippedOptions[] = sprintf(
                    '--ignore-table=%s.%s',
                    $this->databaseName,
                    $table
                );
            }
            $this->options = array_merge($this->options, $skippedOptions);
        }
        if ($input->getOption('debug')) {
            $this->options = array_merge($this->options, $this->debugOptions);
        }

        $this->dumpCmd->setArguments($this->options);
        $this->dumpCmd->add($this->databaseName);

    }

    /**
     * Execute command and tell about it.
     * @param  InputInterface  $input  user input container
     * @param  OutputInterface $output output handler
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $process = $this->dumpCmd->getProcess();
        $process->mustRun();

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo 'OUT > '.$buffer;
            }
        });

        if ($input->getOption('compress')) {
            $this->zipCmd->setArguments(array($this->resultFileName));
            $this->zipCmd->getProcess()->mustRun();
            $this->resultFileName = sprintf('%s.gz', $this->resultFileName);
        }

        $msg = sprintf('Dumped database to %s. ', $this->resultFileName);
        $output->write($msg);

        $size  = filesize($this->resultFileName);
        $size /= (1024 * 1024);
        $msg   = sprintf('Resulting file size: %.6s MB.', $size);
        $output->writeln($msg);
    }
}
