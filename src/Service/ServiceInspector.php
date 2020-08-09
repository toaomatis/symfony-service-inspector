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

    /** @var array */
    private array $hashes;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->hashes = [];
    }

    /**
     * @param string $yamlFilePath
     */
    public function inspect(string $yamlFilePath): void
    {
        $yamlFile = new YamlFile($yamlFilePath, $this->logger);
        $yamlFile->parse();
        $this->createHashmap($yamlFile);
    }

    private function createHashmap(YamlFile $yamlFile): void
    {
        $services = $yamlFile->getServices();
        foreach ($services as $name => $service) {
            if ($name === '_defaults') {
                continue;
            }
            $serialized = serialize($service);
            $hash = md5($serialized);
            if (array_key_exists($hash, $this->hashes) === true) {
                $matchedName = $this->hashes[$hash];
                $this->logger->warning(sprintf('Found duplicate hash for service "%s" --> "%s"', $name, $matchedName));
                continue;
            }
            $this->hashes[$hash] = $name;
        }
        $imports = $yamlFile->getImports();
        foreach ($imports as $import) {
            $this->createHashmap($import);
        }
    }
}