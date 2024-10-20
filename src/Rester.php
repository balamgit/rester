<?php

namespace Itsmg\Rester;

class Rester
{
    use BaseFetchDna, AssignApiDataPoints;

    protected string $baseUrl = '';

    protected array $requestHeaders = [];

    protected array $payloads = [];

    protected string $endPoint = '';

    protected string $apiRoute = '';

    protected $responseContent;

    protected ?int $responseStatusCode;

    protected $responseHeaders;

    protected string $method = 'post';

    protected bool $log = false;

    protected string $logConnection = 'default';

    protected string $logCollection = 'rester_api_logs';

    protected array $loggable = [];

    protected $payloadBeforeIntercept;

    protected $responseBeforeIntercept;

    protected $requestHeaderBeforeIntercept;

    protected $responseHeaderBeforeIntercept;

    protected bool $isEndPointOverWrite = false;

    protected string $appendEndPoint = '';

    public function getContent()
    {
        return $this->responseContent;
    }

    public function jsonToArray()
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

    public static function fetch(array $payload = [], array $headers = [])
    {
        return (new static())
            ->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->get();
    }

    public static function fetchContent(array $payload = [], array $headers = [])
    {
        return (new static())
            ->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->getContent();
    }

    public static function fetchStatusCode(array $payload = [], array $headers = [])
    {
        return (new static())
            ->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->getContent();
    }

    public static function fetchJsonToArray(array $payload = [], array $headers = [])
    {
        return (new static())
            ->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->jsonToArray();
    }

    public static function fetchResponseHeaders(array $payload = [], array $headers = [])
    {
        return (new static())
            ->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->getResponseHeaders();
    }
}
