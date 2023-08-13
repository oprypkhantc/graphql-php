<?php

require __DIR__ . '/../../vendor/autoload.php';

use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Handler\NotFoundHandler;
use Laminas\Stratigility\Middleware\CallableMiddlewareDecorator;
use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Laminas\Stratigility\MiddlewarePipe;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

$config = ServerConfig::create([
	'schema' => new Schema([
		'query' => new ObjectType([
			'name' => 'Query',
			'fields' => [
				'x' => [
					'type' => Type::id(),
				],
			]
		]),
	]),
]);
$server = new StandardServer($config);

$app = new MiddlewarePipe();
$app->pipe(new CallableMiddlewareDecorator(function (ServerRequestInterface $request) use ($server): ResponseInterface {
	return $server->processPsrRequest($request);
}));

$errorResponseGenerator = static function (Throwable $e) {
    $response = (new ResponseFactory())->createResponse(500);
    $response->getBody()->write(sprintf(
        'An error occurred: %s',
        $e->getMessage()
    ));
    return $response;
};
$emitter = new SapiEmitter();

$server = new RequestHandlerRunner(
    $app,
    $emitter,
    static function () {
        return ServerRequestFactory::fromGlobals();
    },
    $errorResponseGenerator,
);

try {
    $server->run();
} catch (Throwable $e) {
    $emitter->emit($errorResponseGenerator($e));
}
