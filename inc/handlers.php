<?php

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Interfaces\ErrorRendererInterface;
use Slim\Exception\HttpInternalServerErrorException;

class JsonErrorRenderer implements ErrorRendererInterface
{

  public const BAD_REQUEST = 'BAD_REQUEST';
  public const INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';
  public const NOT_ALLOWED = 'NOT_ALLOWED';
  public const NOT_IMPLEMENTED = 'NOT_IMPLEMENTED';
  public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
  public const SERVER_ERROR = 'SERVER_ERROR';
  public const UNAUTHENTICATED = 'UNAUTHENTICATED';

  public function __invoke(Throwable $exception, bool $displayErrorDetails): string
  {
    $title = 'Error';
    $statusCode = 500;
    $type = self::SERVER_ERROR;
    $message = 'An internal error has occurred while processing your request.';
    $description = 'Undefined error. Contact your system administrator.';
    
    if ($exception instanceof HttpException) {
      $statusCode = $exception->getCode();
      $message = $exception->getMessage();
      $title = $exception->getTitle();
      $description = $exception->getDescription();
      
      if ($exception instanceof HttpNotFoundException) {
        $type = self::RESOURCE_NOT_FOUND;
      } elseif ($exception instanceof HttpInternalServerErrorException) {
        $type = self::SERVER_ERROR;
      } elseif ($exception instanceof HttpMethodNotAllowedException) {
        $type = self::NOT_ALLOWED;
      } elseif ($exception instanceof HttpUnauthorizedException) {
        $type = self::UNAUTHENTICATED;
      } elseif ($exception instanceof HttpForbiddenException) {
        $type = self::UNAUTHENTICATED;
      } elseif ($exception instanceof HttpBadRequestException) {
        $type = self::BAD_REQUEST;
      } elseif ($exception instanceof HttpNotImplementedException) {
        $type = self::NOT_IMPLEMENTED;
      }
    }

    $error = [
      'title' => $title,
      'description' => $description,
      'message' => $message,
    ];

    $details = $displayErrorDetails ? [
      'type' => $type,
      'code' => $statusCode,
      'file' => $exception->getFile(),
      'line' => $exception->getLine(),
      'trace' => $exception->getTrace(),
    ] : [];

    return $this->renderError($error, $details);
  }

  public function renderError(array $error = [], array $details = []): string
  {
    $payload = array_merge($error,$details);
    return json_encode($payload, JSON_UNESCAPED_UNICODE);
  }
}

$displayErrorDetails = (bool)($_ENV['DEBUG'] ?? false);

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
// $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);

// Get the default error handler and register my custom error renderer.
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->registerErrorRenderer('application/json', JsonErrorRenderer::class);
$errorHandler->forceContentType('application/json');
