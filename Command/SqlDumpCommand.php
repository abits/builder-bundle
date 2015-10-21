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
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        self::$MYSQLDUMPCMD = exec('which mysqldump');
        self::$COMPRESSCMD  = exec('which gzip');
        $dumpDate           = date('Ymd_Hms');
        $databaseHost       = escapeshellarg($this->getContainer()->getParameter('database_host'));
        $databasePort       = escapeshellarg($this->getContainer()->getParameter('database_port'));
        $databaseName       = escapeshellarg($this->getContainer()->getParameter('database_name'));
        $databaseUser       = escapeshellarg($this->getContainer()->getParameter('database_user'));
        $databasePassword   = escapeshellarg($this->getContainer()->getParameter('database_password'));
        $resultFileName     = sprintf('%s_%s.sql', $this->getContainer()->getParameter('database_name'), $dumpDate);


        $databaseHostParameter     = $databaseHost ? sprintf(' --host=%s', $databaseHost) : '';
        $databasePortParameter     = $databasePort ? sprintf(' --port=%s', $databasePort) : '';
        $databaseUserParameter     = $databaseUser ? sprintf(' --user=%s', $databaseUser) : '';
        $databasePasswordParameter = $databasePassword ? sprintf(' --password=%s', $databasePassword) : '';
        if ($databaseName) {
            $databaseNameArgument  = sprintf(' %s', $databaseName);
        }
        else {
            throw new InvalidArgumentException('Cannot find database name in your parameters.yml.');
        }

        $cmd = sprintf('%s%s%s%s%s%s', self::$MYSQLDUMPCMD,
                                       $databaseHostParameter,
                                       $databasePortParameter,
                                       $databaseUserParameter,
                                       $databasePasswordParameter,
                                       $databaseNameArgument);

        if ($input->getOption('compress')) {
            $resultFileName = sprintf('%s.gz', $resultFileName);
            $append_cmd = sprintf(' | %s > %s', self::$COMPRESSCMD,
                                                escapeshellarg($resultFileName));
        } else {
            $append_cmd = sprintf(' > %s', escapeshellarg($resultFileName));
        }
        $msg = sprintf('Dumped database to %s. ', $resultFileName);
        $cmd .= $append_cmd;
        shell_exec($cmd);
        $output->write($msg);
        $size = filesize($resultFileName);
        $size /= (1024 * 1024);
        $msg = sprintf('Resulting file size: %.6s MB.', $size);
        $output->writeln($msg);
    }

}
