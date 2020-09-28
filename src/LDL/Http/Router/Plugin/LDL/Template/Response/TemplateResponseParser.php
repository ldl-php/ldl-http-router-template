<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Template\Response;

use LDL\Http\Router\Plugin\LDL\Template\Engine\Repository\TemplateEngineRepository;
use LDL\Http\Router\Plugin\LDL\Template\Repository\TemplateFileRepository;
use LDL\Http\Router\Response\Parser\ResponseParserInterface;
use LDL\Http\Router\Router;

class TemplateResponseParser implements ResponseParserInterface
{

    public const NAMESPACE = 'ldl.response.parser';
    public const NAME = 'template';
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
    private $test = 0;

    public function __construct(
        TemplateFileRepository $fileRepository,
        TemplateEngineRepository $engineRepository
    )
    {
        $this->fileRepository = $fileRepository;
        $this->engineRepository = $engineRepository;
    }

    public function getNamespace(): string
    {
        return self::NAMESPACE;
    }

    public function getName() : string
    {
        return self::NAME;
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
        $engine = $this->engineRepository->getSelectedEngine();

        if(!array_key_exists($response->getStatusCode(), $this->templates)){
            throw new \RuntimeException("Template for HTTP Response code \"$statusCode\" not found");
        }

        $templateFile = $this->fileRepository->get($this->templates[$statusCode])->getRealPath();
        return $engine->render($templateFile, $data);
    }
}