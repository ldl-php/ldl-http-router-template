<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Template\Config;

use LDL\Http\Router\Plugin\LDL\Template\Engine\Repository\TemplateEngineRepository;
use LDL\Http\Router\Plugin\LDL\Template\Response\TemplateResponseParser;
use LDL\Http\Router\Response\Parser\Repository\ResponseParserRepository;
use LDL\Http\Router\Response\Parser\Repository\ResponseParserRepositoryInterface;
use LDL\Http\Router\Route\Config\Helper\ResponseCodeHelper;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserInterface;
use LDL\Http\Router\Route\Factory\Exception\SchemaException;
use LDL\Http\Router\Route\RouteInterface;

class TemplateConfigParser implements RouteConfigParserInterface
{
    /**
     * @var TemplateEngineRepository
     */
    private $engineRepository;

    /**
     * @var ResponseParserRepositoryInterface
     */
    private $responseParserRepository;

    public function __construct(
        TemplateEngineRepository $engineRepository,
        ResponseParserRepository $responseParserRepository
    )
    {
        $this->engineRepository = $engineRepository;
        $this->responseParserRepository = $responseParserRepository;
    }

    /**
     * @param array $config
     * @param RouteInterface $route
     * @param string|null $file
     * @throws Exception\TemplateConfigParserEngineException
     * @throws SchemaException
     * @throws \LDL\Framework\Base\Exception\LockingException
     * @throws \LDL\Type\Collection\Exception\CollectionKeyException
     */
    public function parse(
        array $config,
        RouteInterface $route,
        string $file = null
    ): void
    {
        if(count($this->engineRepository) === 0){
            $msg = 'No template engines found in template engine repository';
            throw new Exception\TemplateConfigParserEngineException($msg);
        }

        $engine = $this->getEngine($config);

        if(null === $engine){
            throw new SchemaException('Template engine specification was not found in route configuration');
        }

        $templateResponseParsers = $this->responseParserRepository
            ->filterByInterface(TemplateResponseParser::class);

        if(!count($templateResponseParsers)){
            return;
        }

        $this->engineRepository->select($engine);

        $responseCodes = $this->getTemplatesByResponseCode($config);


        foreach($responseCodes as $code => $template){
            /**
             * @var TemplateResponseParser $templateResponseParser
             */
            foreach($templateResponseParsers as $templateResponseParser){
                $templateResponseParser->addTemplate($code, $template);
            }
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

    private function getTemplatesByResponseCode(array $config) : ?array
    {
        if(false ===  array_key_exists('codes', $config['response']['template'])) {
            return null;
        }

        $responseCodes = $config['response']['template']['codes'];

        $codes = [];
        foreach($responseCodes as $pattern => $value) {
            if (!array_key_exists('file', $value)) {
                throw new SchemaException('Invalid template section, missing file');
            }

            $getCodes = ResponseCodeHelper::generate((string) $pattern, $value['file']);

            foreach($getCodes as $code => $file){
                $codes[$code] = $file;
            }
        }

        return $codes;
    }
}