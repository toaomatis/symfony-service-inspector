<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\YamlFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ServiceInspector
{
    /** @var ContainerInterface */
    private ContainerInterface $container;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var string|null */
    private ?string $yamlFilePath;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->yamlFilePath = null;
    }

    /**
     * @param string $yamlFilePath
     */
    public function setYamlFilePath(string $yamlFilePath): void
    {
        $this->yamlFilePath = $yamlFilePath;
    }

    public function inspect(): void
    {
        $yamlFile = new YamlFile($this->yamlFilePath, $this->logger);
        $yamlFile->parse();
    }
}