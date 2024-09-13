<?php

namespace Itsmg\Rester\Exceptions;

use Exception;

class ResterApiException extends Exception
{
    protected $httpStatusCode;

    protected $responseBody;

    public function __construct($message, $httpStatusCode = 500, $responseBody = null)
    {
        parent::__construct($message);
        $this->httpStatusCode = $httpStatusCode;
        $this->responseBody = $responseBody;
    }

    /**
     * Get the HTTP status code for the exception.
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the responseContentHandler body for the exception.
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }
}
