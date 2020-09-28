<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Template\Engine;

use LDL\Template\Contracts\TemplateEngineInterface;

class PhpTemplateEngine implements TemplateEngineInterface
{
    public function renderFromString(string $string, $values): string
    {
        throw new \RuntimeException('Not implemented');
    }

    public function render(string $template, $data): string
    {
        ob_start();
        require $template;
        $result = ob_get_clean();
        return false === $result ? '' : $result;
    }
}