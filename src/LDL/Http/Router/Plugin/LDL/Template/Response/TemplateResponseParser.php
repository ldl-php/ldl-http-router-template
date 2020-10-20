<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Template\Response;

use LDL\Http\Router\Plugin\LDL\Template\Engine\Repository\TemplateEngineRepository;
use LDL\Http\Router\Plugin\LDL\Template\Repository\TemplateFileRepository;
use LDL\Http\Router\Response\Parser\AbstractResponseParser;
use LDL\Http\Router\Router;

class TemplateResponseParser extends AbstractResponseParser
{

    private const NAMESPACE = 'ldl.response.parser';
    private const NAME = 'template';

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
     * @var TemplateFileRepository
     */
    private $fileRepository;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $name;

    public function __construct(
        TemplateFileRepository $fileRepository,
        TemplateEngineRepository $engineRepository,
        string $namespace = null,
        string $name=null
    )
    {
        $this->fileRepository = $fileRepository;
        $this->engineRepository = $engineRepository;
        $this->namespace = $namespace ?? self::NAMESPACE;
        $this->name = $name ?? self::NAME;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
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

    public function parse(array $data, string $context, Router $router): string
    {
        $response = $router->getResponse();
        $statusCode = $response->getStatusCode();
        $engine = $this->engineRepository->getSelectedItem();

        if(!array_key_exists($response->getStatusCode(), $this->templates)){
            throw new \RuntimeException("Template for HTTP Response code \"$statusCode\" not found");
        }

        $templateFile = $this->fileRepository->get($this->templates[$statusCode])->getRealPath();
        return $engine->render($templateFile, $data);
    }
}