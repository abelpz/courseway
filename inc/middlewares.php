<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpBadRequestException;


class ExampleAfterMiddleware
{
  /**
   * Example middleware invokable class
   *
   * @param  Request  $request PSR-7 request
   * @param  RequestHandler $handler PSR-15 request handler
   *
   * @return Response
   */
  public function __invoke(Request $request, RequestHandler $handler): Response
  {
    $body = $request->getBody();
    
    if($request->getHeaderLine('Content-Type') == 'application/json' && !json_decode($body->getContents(), true)){
      throw new HttpBadRequestException($request, 'Check your request syntax.');
    }

    $body->rewind();
    $response = $handler->handle($request);
    return $response;
  }
}
