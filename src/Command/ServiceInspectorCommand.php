<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\ServiceInspector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ServiceInspectorCommand extends Command
{
    public const ARG_YAML_FILE = 'yaml_file';
    protected static $defaultName = 'app:inspect-services';

    /** @var ServiceInspector */
    private ServiceInspector $serviceInspector;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * ServiceInspectorCommand constructor.
     * @param ServiceInspector $serviceInspector
     * @param LoggerInterface  $logger
     */
    public function __construct(ServiceInspector $serviceInspector, LoggerInterface $logger)
    {
        $this->serviceInspector = $serviceInspector;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Inspects Symfony Services and searches for duplicate and unused configurations')
            ->addArgument(self::ARG_YAML_FILE, InputArgument::REQUIRED, 'The (root) YAML services file.')
            ->setHelp('This command allows you to check for duplicate and unused Symfony Services.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $yamlFile = $input->getArgument(self::ARG_YAML_FILE);
        $yamlFilePath = realpath($yamlFile);
        if ($yamlFilePath === false) {
            $this->logger->error(sprintf('YAML file "%s" not found! Exiting...', $yamlFile));

            return Command::FAILURE;
        }
        $this->logger->info('Starting inspection...');
        $this->serviceInspector->inspect($yamlFilePath);
        $this->logger->info('Finished inspection.');

        return Command::SUCCESS;
    }
}