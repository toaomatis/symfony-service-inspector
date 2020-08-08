<?php
declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ServiceInspectorCommand extends Command
{
    protected static $defaultName = 'app:inspect-services';

    protected function configure(): void
    {
        $this
            ->setDescription('Inspects Symfony Services and searches for duplicate and unused configurations')
            ->addArgument('yaml_file', InputArgument::REQUIRED, 'The (root) YAML services file.')
            ->setHelp('This command allows you to check for duplicate and unused Symfony Services.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $output->writeln('Starting inspection...');

        $output->writeln('Finished inspection.');

        return Command::SUCCESS;
    }
}