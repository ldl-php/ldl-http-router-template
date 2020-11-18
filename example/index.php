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
use LDL\Http\Router\Router;
use LDL\Http\Router\Route\Factory\RouteFactory;
use LDL\Http\Router\Route\Group\RouteGroup;
use LDL\Http\Router\Plugin\LDL\Template\Config\TemplateConfigParser;
use LDL\Http\Router\Plugin\LDL\Template\Finder\TemplateFileFinder;
use LDL\Http\Router\Plugin\LDL\Template\Engine\Repository\TemplateEngineRepository;
use LDL\Http\Router\Plugin\LDL\Template\Engine\PhpTemplateEngine;
use LDL\Http\Router\Response\Parser\Repository\ResponseParserRepository;
use LDL\Http\Router\Plugin\LDL\Template\Response\TemplateResponseParser;
use LDL\Http\Router\Middleware\AbstractMiddleware;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserRepository;
use LDL\Http\Router\Middleware\DispatcherRepository;

use Symfony\Component\HttpFoundation\ParameterBag;

class TemplateDispatcher extends AbstractMiddleware
{
    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        Router $router,
        ParameterBag $parameterBag = null
    ) : ?array
    {
        return [
            'name' => $parameterBag->get('urlName')
        ];
    }
}

$templateFileFinder = new TemplateFileFinder(__DIR__.'/template');
$templateEngineRepository = new TemplateEngineRepository();
$templateEngineRepository->append(new PhpTemplateEngine(),'template.engine.php');

$responseParserRepository = new ResponseParserRepository();
$responseParserRepository->append(new TemplateResponseParser($templateFileFinder, $templateEngineRepository));

$routerExceptionHandlers = new ExceptionHandlerCollection();
$routerExceptionHandlers->append(new HttpMethodNotAllowedExceptionHandler('http.method.not.allowed'))
    ->append(new HttpRouteNotFoundExceptionHandler('http.route.not.found'))
    ->append(new InvalidContentTypeExceptionHandler('http.invalid.content'));

$parserRepo = new RouteConfigParserRepository();

$parserRepo->append(
    new TemplateConfigParser(
        $templateEngineRepository,
        $responseParserRepository
    )
);

$response = new Response();

$router = new Router(
    Request::createFromGlobals(),
    $response,
    $parserRepo,
    new ExceptionHandlerCollection(),
    $responseParserRepository
);

$dispatcherRepository = new DispatcherRepository();
$dispatcherRepository->append(new TemplateDispatcher('template.dispatcher'));

$routes = RouteFactory::fromJsonFile(
    './routes.json',
    $router,
    $dispatcherRepository
);

$group = new RouteGroup('Test Group', 'test', $routes);

$router->addGroup($group);

$router->dispatch()->send();
