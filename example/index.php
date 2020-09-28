<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\Http\Core\Request\Request;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\Response;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Handler\Exception\Collection\ExceptionHandlerCollection;
use LDL\Http\Router\Handler\Exception\Handler\HttpMethodNotAllowedExceptionHandler;
use LDL\Http\Router\Handler\Exception\Handler\HttpRouteNotFoundExceptionHandler;
use LDL\Http\Router\Handler\Exception\Handler\InvalidContentTypeExceptionHandler;
use LDL\Http\Router\Route\Dispatcher\RouteDispatcherInterface;
use LDL\Http\Router\Router;
use LDL\Http\Router\Route\Factory\RouteFactory;
use LDL\Http\Router\Route\Group\RouteGroup;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserCollection;
use LDL\Http\Router\Plugin\LDL\Template\Config\TemplateConfigParser;
use LDL\Http\Router\Plugin\LDL\Template\Repository\TemplateFileRepository;
use LDL\Http\Router\Plugin\LDL\Template\Engine\Repository\TemplateEngineRepository;
use LDL\Http\Router\Plugin\LDL\Template\Engine\PhpTemplateEngine;
use LDL\Http\Router\Response\Parser\Repository\ResponseParserRepository;
use LDL\Http\Router\Plugin\LDL\Template\Response\TemplateResponseParser;

class Dispatcher implements RouteDispatcherInterface
{
    public function dispatch(
        RequestInterface $request,
        ResponseInterface $response
    ) : ?array
    {
        return [
            'name' => $request->get('name')
        ];
    }
}

$templateFileRepository = new TemplateFileRepository(__DIR__.'/template');
$templateEngineRepository = new TemplateEngineRepository();

$templateEngineRepository->append(new PhpTemplateEngine(),'template.engine.php');

$responseParserRepository = new ResponseParserRepository();
$responseParserRepository->append(new TemplateResponseParser($templateFileRepository, $templateEngineRepository));

$exceptionHandlerCollection = new ExceptionHandlerCollection();
$exceptionHandlerCollection->append(new HttpMethodNotAllowedExceptionHandler());
$exceptionHandlerCollection->append(new HttpRouteNotFoundExceptionHandler());
$exceptionHandlerCollection->append(new InvalidContentTypeExceptionHandler());

$parserCollection = new RouteConfigParserCollection();

$parserCollection->append(
    new TemplateConfigParser(
        $templateEngineRepository
    )
);

$response = new Response();

$router = new Router(
    Request::createFromGlobals(),
    $response,
    $exceptionHandlerCollection,
    $responseParserRepository
);

$routes = RouteFactory::fromJsonFile(
    './routes.json',
    $router,
    null,
    $parserCollection
);

$group = new RouteGroup('Test Group', 'test', $routes);

$router->addGroup($group);

$router->dispatch()->send();
