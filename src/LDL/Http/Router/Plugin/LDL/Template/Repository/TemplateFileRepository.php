<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Template\Repository;

use LDL\Template\Contracts\TemplateEngineInterface;

class TemplateFileRepository
{
    /**
     * @var string
     */
    private $baseDirectory;

    public function __construct(string $baseDirectory)
    {
        if(!is_dir($baseDirectory)){
            $msg = "Base template directory: \"$baseDirectory\" is not a directory";
            throw new Exception\BaseDirectoryException($msg);
        }

        if(!is_readable($baseDirectory)){
            $msg = "Base template directory: \"$baseDirectory\" is not a readable";
            throw new Exception\BaseDirectoryException($msg);
        }

        $this->baseDirectory = $baseDirectory;
    }

    public function get(string $template) : \SplFileInfo
    {
        $template = implode(\DIRECTORY_SEPARATOR, [$this->baseDirectory, $template]);

        if(!file_exists($template)){
            $msg = "Template file: \"$template\" was not found";
            throw new Exception\TemplateException($msg);
        }

        if(!is_readable($template)){
            $msg = "Template file: \"$template\" is not readable, check file permissions";
            throw new Exception\TemplateException($msg);
        }

        return new \SplFileInfo($template);
    }

    public function getFirst() : TemplateEngineInterface
    {
        return current($this);
    }

}