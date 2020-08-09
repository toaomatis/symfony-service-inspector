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

    /** @var string[] */
    private array $hashes;

    /** @var int[] */
    private array $references;

    /** @var int */
    private int $duplicateCounter;

    /** @var int */
    private int $unreferencedCounter;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->hashes = [];
        $this->references = [];
        $this->duplicateCounter = 0;
        $this->unreferencedCounter = 0;
    }

    /**
     * @param string $yamlFilePath
     */
    public function inspect(string $yamlFilePath): void
    {
        $yamlFile = new YamlFile($yamlFilePath, $this->logger);
        $yamlFile->parse();
        $this->createHashmap($yamlFile);
        $this->countReferences($yamlFile);
        $this->reportReferences();
        $this->logger->warning(sprintf('Found %d duplicates in "%s" recursively.', $this->duplicateCounter,
            $yamlFilePath));
        $this->logger->warning(sprintf('Found %d unreferenced in "%s" recursively.', $this->unreferencedCounter,
            $yamlFilePath));
    }

    private function createHashmap(YamlFile $yamlFile): void
    {
        $services = $yamlFile->getServices();
        foreach ($services as $name => $service) {
            if ($name === '_defaults') {
                continue;
            }
            if (array_key_exists($name, $this->references) === false) {
                $this->references[$name] = 0;
            }
            $serialized = serialize($service);
            $hash = md5($serialized);
            if (array_key_exists($hash, $this->hashes) === true) {
                $matchedName = $this->hashes[$hash];
                if ($matchedName !== $name) {
                    $this->logger->warning(
                        sprintf('Found duplicate hash for service "%s" --> "%s".', $name, $matchedName));
                    $this->duplicateCounter++;

                } else {
                    $this->logger->debug(sprintf('Found duplicate hash and service "%s".', $name));
                }
                continue;
            }
            $this->hashes[$hash] = $name;
        }
        $imports = $yamlFile->getImports();
        foreach ($imports as $import) {
            $this->createHashmap($import);
        }
    }

    /**
     * @param YamlFile $yamlFile
     */
    private function countReferences(YamlFile $yamlFile): void
    {
        $services = $yamlFile->getServices();
        foreach ($services as $name => $service) {
            if ($name === '_defaults') {
                continue;
            }
            $factory = $service['factory'] ?? [];
            if (is_array($factory) === false) {
                $factories = explode(':', $factory);
                $factory = ['@' . $factories[0]];
            }

            $this->examineReferences($service['arguments'] ?? []);
            $this->examineReferences($service['calls'] ?? []);
            $this->examineReferences($factory ?? []);
            //$this->examineReferences($service['tags'] ?? []);
        }
        $imports = $yamlFile->getImports();
        foreach ($imports as $import) {
            $this->countReferences($import);
        }
    }

    /**
     * @param array $references
     */
    private function examineReferences(array $references): void
    {
        foreach ($references as $key => $reference) {
            if (is_array($reference) === true) {
                $this->examineReferences($reference);

                continue;
            }

            $name = null;

            $this->logger->debug(sprintf('Key "%s" Reference "%s"', $key, $reference));
            if ((is_string($reference) === true) && (empty($reference) === false) && ($reference[0] === '@')) {
                $name = substr($reference, 1);
                $this->addReference($name);
            } elseif ((is_string($key) === true) && (empty($key) === false) && (strpos($key, '\\') !== false)) {
                $name = $reference;
                $this->addReference($name);
            } elseif ((is_string($reference) === true) && (empty($reference) === false) &&
                (strpos($reference, '.') !== false)) {
                $name = $reference;
                $this->addReference($name, false);
            }

        }
    }

    private function addReference(string $name, bool $forceAdd = true): void
    {
        if (array_key_exists($name, $this->references) === false) {
            if ($forceAdd === true) {
                $this->logger->warning(sprintf('Found unreferenced "%s".', $name));
                $this->references[$name] = 1;
            } else {
                $this->logger->debug(sprintf('Skipping unreferenced "%s".', $name));
            }
        } else {
            $this->references[$name] += 1;
        }
    }

    private function reportReferences(): void
    {
        foreach ($this->references as $name => $count) {
            $this->logger->debug(sprintf('Service "%s" is used %d times', $name, $count));
            if ($count === 0) {
                $this->logger->warning(sprintf('Unreferenced Service "%s"', $name));
                $this->unreferencedCounter++;
            }
        }
    }
}