<?php

namespace Itsmg\Rester;

use BadMethodCallException;
use Itsmg\Rester\Constants\AllowedMethod;
use Itsmg\Rester\Constants\ContentType;
use Itsmg\Rester\Contracts\LogStrategy;

class Rester
{
    use BaseFetchDna, AssignApiDataPoints;

    protected string $baseUrl = '';

    protected array $requestHeaders = [];

    protected array $payloads = [];

    protected string $endPoint = '';

    protected string $apiRoute = '';

    public ContentType $contentType = ContentType::JSON;

    protected $responseContent;

    protected ?int $responseStatusCode;

    protected $responseHeaders;

    protected AllowedMethod $method = AllowedMethod::POST;

    protected bool $log = false;

    protected ?LogStrategy $logStrategy = null;

    protected array $loggable = [];

    protected $payloadBeforeIntercept;

    protected $responseBeforeIntercept;

    protected $requestHeaderBeforeIntercept;

    protected $responseHeaderBeforeIntercept;

    protected bool $isEndPointOverWrite = false;

    protected string $appendEndPoint = '';

    public static function __callStatic($method, $arguments)
    {
        $instance = new static();

        if (method_exists($instance, $method)) {
            return $instance->{$method}(...$arguments);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }

    public function getStatusCode(): ?int
    {
        return $this->responseStatusCode;
    }

    public function fetch(array $payload = [], array $headers = []): array
    {
        return $this->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->get();
    }

    public function get(): array
    {
        return [
            'content' => $this->responseContent,
            'headers' => $this->responseHeaders,
            'status_code' => $this->responseStatusCode,
        ];
    }

    public function fetchContent(array $payload = [], array $headers = [])
    {
        return $this->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->getContent();
    }

    public function getContent()
    {
        return $this->responseContent;
    }

    public function fetchStatusCode(array $payload = [], array $headers = [])
    {
        return $this->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->getContent();
    }

    public function fetchJsonToArray(array $payload = [], array $headers = [])
    {
        return $this->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->jsonToArray();
    }

    public function jsonToArray()
    {
        return json_decode($this->responseContent, true);
    }

    public function fetchResponseHeaders(array $payload = [], array $headers = [])
    {
        return $this->addPayloads($payload)
            ->addHeaders($headers)
            ->send()
            ->getResponseHeaders();
    }

    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }
}
