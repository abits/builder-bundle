<?php
namespace BuilderBundle\Command;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class SqlDumpCommand extends ContainerAwareCommand
{
    protected static $MYSQLDUMPCMD;
    protected static $COMPRESSCMD;

    protected function configure()
    {
        $this
            ->setName('fuf:sql-dump')
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

    protected function initialize(InputInterface $input, OutputInterface $output) {
        self::$MYSQLDUMPCMD   = exec('which mysqldump');
        self::$COMPRESSCMD    = exec('which gzip');
        $dumpDate             = date('Ymd_Hms');
        $defaultOptions       = ' --dump-date'.
                                ' --comments'.
                                ' --add-drop-table'.
                                ' --add-locks'.
                                ' --create-options';

        $databaseHost         = escapeshellarg($this->getContainer()->getParameter('database_host'));
        $databasePort         = escapeshellarg($this->getContainer()->getParameter('database_port'));
        $databaseName         = escapeshellarg($this->getContainer()->getParameter('database_name'));
        $databaseUser         = escapeshellarg($this->getContainer()->getParameter('database_user'));
        $databasePassword     = escapeshellarg($this->getContainer()->getParameter('database_password'));

        if (!$databaseName) {
            throw new InvalidArgumentException('Cannot find database name in your parameters.yml.');
        }

        $databaseHostParameter      = $databaseHost     ? sprintf(' --host=%s', $databaseHost) : '';
        $databasePortParameter      = $databasePort     ? sprintf(' --port=%s', $databasePort) : '';
        $databaseUserParameter      = $databaseUser     ? sprintf(' --user=%s', $databaseUser) : '';
        $databasePasswordParameter  = $databasePassword ? sprintf(' --password=%s', $databasePassword) : '';

        $this->debugOptions         = ' --debug-check'.
                                      ' --debug-info';
        $this->resultFileName       = sprintf('%s_%s.sql', 
            $this->getContainer()->getParameter('database_name'), $dumpDate);
        $this->databaseNameArgument = sprintf(' %s', $databaseName);
        $this->cmd                  = sprintf('%s%s%s%s%s%s', 
                                                self::$MYSQLDUMPCMD,
                                                $databaseHostParameter,
                                                $databasePortParameter,
                                                $databaseUserParameter,
                                                $databasePasswordParameter,
                                                $defaultOptions);
    }

    protected function interact(InputInterface $input, OutputInterface $output) {
        $append_cmd = '';
        if ($input->getOption('skip')) {

        }
        if ($input->getOption('debug')) {
            $append_cmd .= $this->debugOptions;
        }
        $this->cmd .= $append_cmd;

        if ($input->getOption('compress')) {
            $resultFileName = sprintf('%s.gz', $this->resultFileName);
            $append_cmd    .= sprintf(' %s | %s > %s', $this->databaseNameArgument,
                                                       self::$COMPRESSCMD,
                                                       escapeshellarg($this->resultFileName));
        } else {
            $append_cmd    .= sprintf(' %s > %s', $this->databaseNameArgument,
                                                  escapeshellarg($this->resultFileName));
        }
        $this->cmd .= $append_cmd;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        shell_exec($this->cmd);
        $msg = sprintf('Dumped database to %s. ', $this->resultFileName);
        $output->write($msg);

        $size  = filesize($this->resultFileName);
        $size /= (1024 * 1024);
        $msg   = sprintf('Resulting file size: %.6s MB.', $size);
        $output->writeln($msg);
    }

}
