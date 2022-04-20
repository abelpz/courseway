<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace CourseWay\Exception;

use Slim\Exception\HttpSpecializedException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class HttpGenericException extends HttpSpecializedException
{
  public function __construct(ServerRequestInterface $request, ?string $message = null, ?string $code = '500', ?Throwable $previous = null)
  {
    $status_codes = [
      "400" => "Bad Request",
      "401" => "Unauthorized",
      "402" => "Payment Required",
      "403" => "Forbidden",
      "404" => "Not Found",
      "405" => "Method Not Allowed",
      "406" => "Not Acceptable",
      "407" => "Proxy Authentication Required",
      "408" => "Request Timeout",
      "409" => "Conflict",
      "410" => "Gone",
      "411" => "Length Required",
      "412" => "Precondition Failed",
      "413" => "Payload Too Large",
      "414" => "URI Too Long",
      "415" => "Unsupported Media Type",
      "416" => "Range Not Satisfiable",
      "417" => "Expectation Failed",
      "418" => "I'm a Teapot",
      "421" => "Misdirected Request",
      "422" => "Unprocessable Entity",
      "423" => "Locked",
      "424" => "Failed Dependency",
      "425" => "Too Early",
      "426" => "Upgrade Required",
      "428" => "Precondition Required",
      "429" => "Too Many Requests",
      "431" => "Request Header Fields Too Large",
      "451" => "Unavailable For Legal Reasons",
      "500" => "Internal Server Error",
      "501" => "Not Implemented",
      "502" => "Bad Gateway",
      "503" => "Service Unavailable",
      "504" => "Gateway Timeout",
      "505" => "HTTP Version Not Supported",
      "506" => "Variant Also Negotiates",
      "507" => "Insufficient Storage",
      "508" => "Loop Detected",
      "510" => "Not Extended",
      "511" => "Network Authentication Required",
    ];

    if ($message !== null) {
      $this->message = $message;
    }

    if($status_codes[$code]){
      $this->code = (int) $code;
      $this->title = $code . ' ' . $status_codes[$code];
      $this->message = $message ?: $status_codes[$code] . '.';
      $this->description = $message;
    }else{
      $this->code = 500;
      $this->title = 500 . ' ' . $status_codes['500'];
      $this->message = $message ?: $status_codes[500] . '.';
      $this->description = $message;
    }

    parent::__construct($request, $this->message, $previous);
  }
}