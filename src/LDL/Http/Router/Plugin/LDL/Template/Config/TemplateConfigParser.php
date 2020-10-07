<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Template\Config;

use LDL\Http\Router\Plugin\LDL\Template\Engine\Repository\TemplateEngineRepository;
use LDL\Http\Router\Plugin\LDL\Template\Response\TemplateResponseParser;
use LDL\Http\Router\Response\Parser\Repository\ResponseParserRepository;
use LDL\Http\Router\Response\Parser\Repository\ResponseParserRepositoryInterface;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserInterface;
use LDL\Http\Router\Route\Factory\Exception\SchemaException;
use LDL\Http\Router\Route\Route;
use LDL\Type\Collection\Exception\ItemSelectionException;
use LDL\Type\Collection\Exception\UndefinedOffsetException;
use Psr\Container\ContainerInterface;

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
     * @param Route $route
     * @param ContainerInterface|null $container
     * @param string|null $file
     * @throws Exception\TemplateConfigParserEngineException
     * @throws SchemaException
     * @throws \LDL\Framework\Base\Exception\LockingException
     * @throws \LDL\Type\Collection\Exception\CollectionKeyException
     */
    public function parse(
        array $config,
        Route $route,
        ContainerInterface $container = null,
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

        $this->engineRepository->select($engine);

        $responseCodes = $this->getTemplatesByResponseCode($config);


        foreach($responseCodes as $code => $file){
            /**
             * @var TemplateResponseParser $templateResponseParser
             */
            foreach($templateResponseParsers as $templateResponseParser){
                $templateResponseParser->addTemplate($code, $file);
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
        foreach($responseCodes as $code => $value){
            if (!array_key_exists('file', $value)) {
                throw new SchemaException('Invalid template section, missing file');
            }

            if('any' === $code){
                $code = '100-599';
            }

            if(preg_match('#[0-9]+-[0-9]+#', (string) $code)){
                $codes = array_replace($this->parseResponseRange($code, $value['file']), $codes);
                continue;
            }

            if(preg_match('#\,#', (string) $code)){
                $codes = array_replace($this->parseCommaDelimitedResponseRange($code, $value['file']), $codes);
                continue;
            }

            $codes[$code] = $value['file'];

        }

        return $codes;
    }

    private function parseCommaDelimitedResponseRange(string $responseCodes, string $file) : array
    {
        $codes = array_flip(explode(',', $responseCodes));

        array_walk($codes, static function(&$value, $code) use($file){
            if($code < 100 || $code > 599){
                $msg = sprintf(
                    'Invalid HTTP response code: "%s", in response->template->codes section',
                    $code
                );
                throw new SchemaException($msg);

            }

            $value = $file;
        });

        return $codes;
    }

    private function parseResponseRange(string $responseCodes, string $file) : array
    {
        $codes = explode('-', $responseCodes);
        $start = (int) $codes[0];
        $end = (int) $codes[1];

        if($start < 100 || $start > 599){
            $msg = sprintf(
                'Invalid HTTP start response code: "%s", in response->template->codes section',
                $start
            );
            throw new SchemaException($msg);
        }

        if($end < 100 || $end > 599){
            $msg = sprintf(
                'Invalid end response code: "%s", in response->template->codes section',
                $start
            );
            throw new SchemaException($msg);
        }

        if($start >= $end){
            $msg = 'Start response code must be greater than end response code, in response->template->codes section';
            throw new SchemaException($msg);
        }

        return array_map(static function() use ($file){
            return $file;
        }, array_flip(range($start, $end)));
    }
}