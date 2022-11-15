<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpBadRequestException;


class GroupValidation
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

    $token = $request->getAttribute("token");
    if ($token) {
      $user = UserManager::getManager()->findUserByUsername($token['uname']);
      if (!$user)
        throwException($request, '404', 'User not found.');
      if (!$user->isSuperAdmin())
        throwException($request, '401', 'User must be an administrator.');
    }

    if ($request->getHeaderLine('Content-Type') == 'application/json' && !json_decode($body->getContents(), true)) {
      throw new HttpBadRequestException($request, 'Check your request syntax.');
    }

    $body->rewind();
    $response = $handler->handle($request);
    return $response;
  }
}
