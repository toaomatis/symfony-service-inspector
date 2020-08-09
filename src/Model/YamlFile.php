<?php
declare(strict_types=1);

namespace App\Model;

use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

final class YamlFile
{
    public const IMPORTS = 'imports';
    public const RESOURCE = 'resource';
    public const SERVICES = 'services';

    /** @var string */
    private string $dirname;

    /** @var string */
    private string $basename;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var array */
    private array $services;

    /** @var YamlFile[] */
    private array $imports;

    /**
     * YamlFile constructor.
     * @param string          $filePath
     * @param LoggerInterface $logger
     */
    public function __construct(string $filePath, LoggerInterface $logger)
    {
        $this->dirname = dirname($filePath);
        $this->basename = basename($filePath);
        $this->logger = $logger;
        $this->imports = [];
    }

    public function parse(): void
    {
        $filename = $this->dirname . DIRECTORY_SEPARATOR . $this->basename;
        $this->logger->debug(sprintf('Start working on "%s"', $filename));
        $value = Yaml::parseFile($filename);
        $this->services = $value[self::SERVICES] ?? [];
        $this->logger->debug(sprintf('  Found %d services', count($this->services)));
        $this->recurse($value[self::IMPORTS] ?? []);
        $this->logger->debug(sprintf('Finished "%s"', $filename));
    }

    /**
     * @param string[][] $imports
     */
    private function recurse(array $imports): void
    {
        $this->logger->debug(sprintf('  Found %d imports', count($imports)));
        foreach ($imports as $import) {
            $resource = $import[self::RESOURCE];
            $filepath = $this->dirname . DIRECTORY_SEPARATOR . $resource;
            $yamlFile = new YamlFile($filepath, $this->logger);
            $yamlFile->parse();
            $this->imports[] = $yamlFile;
        }
    }

}