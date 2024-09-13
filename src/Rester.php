<?php

namespace Itsmg\Rester;

class Rester
{
    use BaseFetchDna, AssignApiDataPoints;

    protected string $baseUri = '';

    protected array $requestHeaders = [];

    protected array $requestedData = [];

    protected string $finalEndPoint = '';

    protected string $defaultApiRoute = '';

    protected $responseContent;

    protected ?int $responseStatusCode;

    protected $responseHeaders;

    protected string $method = 'post';

    protected bool $log = false;

    protected string $logConnection = 'default';

    protected string $logCollection = 'rester_api_logs';

    protected array $loggable = [];

    protected $requestBeforeIntercept;

    protected $responseBeforeIntercept;

    protected $requestHeaderBeforeIntercept;

    protected $responseHeaderBeforeIntercept;

    public function getContent()
    {
        return $this->responseContent;
    }

    public function getContentJsonToArray()
    {
        return json_decode($this->responseContent, true);
    }

    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    public function getStatusCode(): ?int
    {
        return $this->responseStatusCode;
    }

    public function get(): array
    {
        return [
            'content' => $this->responseContent,
            'headers' => $this->responseHeaders,
            'status_code' => $this->responseStatusCode,
        ];
    }

}
