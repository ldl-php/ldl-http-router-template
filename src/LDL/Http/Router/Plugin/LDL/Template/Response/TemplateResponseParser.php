<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Template\Response;

use LDL\Http\Router\Plugin\LDL\Template\Engine\Repository\TemplateEngineRepository;
use LDL\Http\Router\Plugin\LDL\Template\Finder\TemplateFileFinder;
use LDL\Http\Router\Response\Parser\AbstractResponseParser;
use LDL\Http\Router\Router;

class TemplateResponseParser extends AbstractResponseParser
{
    private const NAME = 'ldl.response.parser.template';
    public const CONTENT_TYPE = 'text/html; charset=UTF-8';

    /**
     * @var TemplateEngineRepository
     */
    private $engineRepository;

    /**
     * @var array
     */
    private $templates = [];

    /**
     * @var TemplateFileFinder
     */
    private $fileFinder;

    /**
     * @var string
     */
    private $name;

    public function __construct(
        TemplateFileFinder $fileFinder,
        TemplateEngineRepository $engineRepository,
        string $name=null
    )
    {
        $this->fileFinder = $fileFinder;
        $this->engineRepository = $engineRepository;
        $this->name = $name ?? self::NAME;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function addTemplate(int $responseCode, string $template) : self
    {
        $this->templates[$responseCode] = $template;
        return $this;
    }

    public function getContentType(): string
    {
        return self::CONTENT_TYPE;
    }

    public function parse(array $data, Router $router): string
    {
        $response = $router->getResponse();
        $statusCode = $response->getStatusCode();
        $engine = $this->engineRepository->getSelectedItem();

        if(!array_key_exists($response->getStatusCode(), $this->templates)){
            throw new \RuntimeException("Template for HTTP Response code \"$statusCode\" not found");
        }

        $templateFile = $this->fileFinder->get($this->templates[$statusCode])->getRealPath();
        return $engine->render($templateFile, $data);
    }
}