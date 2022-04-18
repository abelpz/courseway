<?php

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpSpecializedException;

use Psr\Http\Message\ServerRequestInterface as Request;

function throwException(Request $request, $code, $message){
  switch ($code) {
    case '400':
      throw new HttpBadRequestException($request, $message);
      break;
    case '401':
      throw new HttpUnauthorizedException($request, $message);
      break;
    case '403':
      throw new HttpForbiddenException($request, $message);
      break;
    case '404':
      throw new HttpNotFoundException($request, $message);
      break;
    case '405':
      throw new HttpMethodNotAllowedException($request, $message);
      break;
    case '500':
      throw new HttpInternalServerErrorException($request, $message);
      break;
    case '501':
      throw new HttpNotImplementedException($request, $message);
      break;
    
    default:
      throw new HttpSpecializedException($request, $message);
      break;
  }

}