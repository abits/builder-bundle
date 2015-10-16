<?php
namespace BuilderBundle\Command;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SqlDumpCommand extends ContainerAwareCommand
{
    protected static $MYSQLDUMPCMD;

    protected function configure()
    {
        $this
            ->setName('fuf:sql-dump')
            ->setDescription('Dump current database to sql.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        self::$MYSQLDUMPCMD = exec('which mysqldump');
        $dumpDate           = date('Ymd_Hms');
        $databaseHost       = $this->getContainer()->getParameter('database_host');
        $databasePort       = $this->getContainer()->getParameter('database_port');
        $databaseName       = $this->getContainer()->getParameter('database_name');
        $databaseUser       = $this->getContainer()->getParameter('database_user');
        $databasePassword   = $this->getContainer()->getParameter('database_password');
        $resultFileName     = sprintf('%s_%s.sql', $databaseName, $dumpDate);


        $databaseHostParameter     = $databaseHost ? sprintf(' --host=%s', $databaseHost) : '';
        $databasePortParameter     = $databasePort ? sprintf(' --port=%s', $databasePort) : '';
        $databaseUserParameter     = $databaseUser ? sprintf(' --user=%s', $databaseUser) : '';
        $databasePasswordParameter = $databasePassword ? sprintf(' --password=%s', $databasePassword) : '';
        if ($databaseName) {
            $databaseNameArgument  = sprintf(' %s', $databaseName);
        }
        else {
            throw new InvalidArgumentException('You must specify a database name in your parameters.yml.');
        }
        $resultFileNameParameter   = sprintf(' --result-file=%s', $resultFileName);

        $cmd = sprintf('%s%s%s%s%s%s%s', self::$MYSQLDUMPCMD,
                                         $databaseHostParameter,
                                         $databasePortParameter,
                                         $databaseUserParameter,
                                         $databasePasswordParameter,
                                         $resultFileNameParameter,
                                         $databaseNameArgument);

        shell_exec(escapeshellcmd($cmd));
        $msg = sprintf('Dumped database to %s.', $resultFileName);
        $output->writeln($msg);
    }
}