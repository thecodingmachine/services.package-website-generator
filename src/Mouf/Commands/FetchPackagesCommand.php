<?php


namespace Mouf\Commands;

use Composer\IO\ConsoleIO;
use Mouf\Services\PackagesInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchPackagesCommand extends Command
{
    /**
     * @var PackagesInstaller
     */
    private $packagesInstaller;

    /**
     * @param PackagesInstaller $packagesInstaller
     */
    public function __construct(PackagesInstaller $packagesInstaller)
    {
        $this->packagesInstaller = $packagesInstaller;
        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('doc:fetch-packages')
            ->setDescription('Fetches packages from Packagist')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $this->packagesInstaller->run(PACKAGIST_USERNAME, $io);

        $output->writeln("Packagist packages download done.");
    }
}