<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Template\Config;

use LDL\Http\Router\Plugin\LDL\Template\Engine\Repository\TemplateEngineRepository;
use LDL\Http\Router\Plugin\LDL\Template\Repository\TemplateFileRepository;
use LDL\Http\Router\Plugin\LDL\Template\Response\Exception\TemplateResponseParserException;
use LDL\Http\Router\Plugin\LDL\Template\Response\TemplateResponseParser;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserInterface;
use LDL\Http\Router\Route\Factory\Exception\SchemaException;
use LDL\Http\Router\Route\Route;
use Psr\Container\ContainerInterface;

class TemplateConfigParser implements RouteConfigParserInterface
{
    /**
     * @var TemplateEngineRepository
     */
    private $engineRepository;

    public function __construct(
        TemplateEngineRepository $engineRepository
    )
    {
        $this->engineRepository = $engineRepository;
    }

    public function parse(
        array $config,
        Route $route,
        ContainerInterface $container = null,
        string $file = null
    ): void
    {
        /**
         * @var TemplateResponseParser $responseParser
         */
        $responseParser = $route->getConfig()->getResponseParser();

        if(!$responseParser instanceof TemplateResponseParser){
            return;
        }

        if(count($this->engineRepository) === 0){
            $msg = 'No template engines found in template engine repository';
            throw new Exception\TemplateConfigParserEngineException($msg);
        }

        $engine = $this->getEngine($config);

        if(null === $engine){
            throw new SchemaException('Template engine specification was not found in route configuration');
        }

        $this->engineRepository->select($engine);

        foreach($this->getTemplates($config) as $responseCode => $template){
            if(!array_key_exists('file', $template)){
                throw new SchemaException('Invalid template section, missing file');
            }

            $responseParser->addTemplate((int) $responseCode, $template['file']);
        }
    }

    private function getEngine(array $config) : ?string
    {
        if(false === array_key_exists('template', $config['response'])){
            return null;
        }

        if(false === array_key_exists('engine', $config['response']['template'])){
            return null;
        }

        return (string) $config['response']['template']['engine'];
    }

    private function getTemplates(array $config) : ?array
    {
        if(false ===  array_key_exists('codes', $config['response']['template'])) {
            return null;
        }

        return $config['response']['template']['codes'];
    }
}