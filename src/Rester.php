<?php
/**
 * RESTER - A PHP package for simplifying REST API interactions
 *
 * RESTER is a PHP package designed to simplify REST API interactions, providing a seamless and elegant way to handle API calls.
 * Inspired by the Eloquent model approach, this package allows developers to define a model for each API call, mirroring the familiar structure of database models.
 *
 * Key Features:
 * - Simplified API interaction with a model-based approach
 * - Grouping of related API calls under a common parent class for enhanced code organization
 * - Encapsulation of REST API logic into manageable models for easy integration of external services
 * - Dynamic configuration of request headers, payload, and endpoint.
 * - Custom logging strategies for API request/response data.
 * - Interceptors for manipulating request and response data.
 * - Support for multiple content types like JSON, form parameters, multipart, etc.
 * - Easily manage API route and base URL.
 *
 * Author: BalaMurugan Periyasamy
 * Date Created: 2025-01-31
 *
 * License: MIT License
 * See the full MIT License in the LICENSE file located in the root directory.
 *
 * @package Itsmg\Rester
 * @version 1.0.6
 */
namespace Itsmg\Rester;

use BadMethodCallException;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleHttp;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Itsmg\Rester\Constants\AllowedMethod;
use Itsmg\Rester\Constants\ContentType;
use Itsmg\Rester\Contracts\AccessLogInterceptor;
use Itsmg\Rester\Contracts\HasFinalEndPoint;
use Itsmg\Rester\Contracts\LogStrategy;
use Itsmg\Rester\Contracts\PayloadInterceptor;
use Itsmg\Rester\Contracts\RequestHeaderInterceptor;
use Itsmg\Rester\Contracts\ResponseContentInterceptor;
use Itsmg\Rester\Contracts\ResponseHeaderInterceptor;
use Itsmg\Rester\Contracts\WithApiRoute;
use Itsmg\Rester\Contracts\WithBaseUrl;
use Itsmg\Rester\Contracts\WithDefaultPayload;
use Itsmg\Rester\Contracts\WithLogStrategy;
use Itsmg\Rester\Contracts\WithRequestHeaders;
use Itsmg\Rester\Exceptions\ResterApiException;

class Rester
{
    /** @var string The base URL for API requests */
    protected string $baseUrl = '';

    /** @var array Default request headers */
    protected array $requestHeaders = [];

    /** @var array Payload data for API requests */
    protected array $payloads = [];

    /** @var string The final endpoint for the API request */
    protected string $endPoint = '';

    /** @var string The API route appended to the base URL */
    protected string $apiRoute = '';

    /** @var ContentType The content type of the request */
    public ContentType $contentType = ContentType::JSON;

    /** @var mixed The response content from the API */
    protected $responseContent;

    /** @var int|null The HTTP status code of the response */
    protected ?int $responseStatusCode = null;

    /** @var array The response headers from the API */
    protected array $responseHeaders = [];

    /** @var AllowedMethod HTTP method used for the request */
    protected AllowedMethod $method = AllowedMethod::POST;

    /** @var bool Whether logging is enabled */
    protected bool $log = false;

    /** @var LogStrategy|null Logging strategy instance */
    protected ?LogStrategy $logStrategy = null;

    /** @var array Data to be logged */
    protected array $loggable = [];

    /** @var mixed Holds intercepted payload before processing */
    protected $payloadBeforeIntercept;

    /** @var mixed Holds intercepted response before processing */
    protected $responseBeforeIntercept;

    /** @var mixed Holds intercepted request headers before processing */
    protected $requestHeaderBeforeIntercept;

    /** @var mixed Holds intercepted response headers before processing */
    protected $responseHeaderBeforeIntercept;

    /** @var bool Whether the endpoint is manually overwritten */
    protected bool $isEndPointOverWrite = false;

    /** @var string The endpoint suffix appended dynamically */
    protected string $appendEndPoint = '';

    public static function __callStatic($method, $arguments)
    {
        $instance = new static();
        if (! method_exists($instance, $method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $reflectionMethod = new \ReflectionMethod($instance, $method);
        if (! $reflectionMethod->isPublic()) {
            throw new BadMethodCallException("Method {$method} is not public.");
        }

        return $instance->{$method}(...$arguments);
    }

    public function getStatusCode(): ?int
    {
        return $this->responseStatusCode;
    }

    /**
     * @throws ResterApiException
     */
    public function fetch(array $payload = [], array $headers = []): array
    {
        return $this->fetchBase($payload, $headers)->get();
    }

    public function get(): array
    {
        return [
            'content' => $this->responseContent,
            'headers' => $this->responseHeaders,
            'status_code' => $this->responseStatusCode,
        ];
    }

    /**
     * @throws ResterApiException
     */
    public function fetchContent(array $payload = [], array $headers = [])
    {
        return $this->fetchBase($payload, $headers)->getContent();
    }

    public function getContent()
    {
        return $this->responseContent;
    }

    /**
     * @throws ResterApiException
     */
    public function fetchStatusCode(array $payload = [], array $headers = []): ?int
    {
        return $this->fetchBase($payload, $headers)->getStatusCode();
    }

    /**
     * @throws ResterApiException
     */
    public function fetchJsonToArray(array $payload = [], array $headers = [])
    {
        return $this->fetchBase($payload, $headers)->jsonToArray();
    }

    public function jsonToArray()
    {
        return json_decode($this->responseContent, true);
    }

    /**
     * @throws ResterApiException
     */
    public function fetchResponseHeaders(array $payload = [], array $headers = []): array
    {
        return $this->fetchBase($payload, $headers)->getResponseHeaders();
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * @throws ResterApiException
     */
    public function send(): self
    {
        $this->assignEndpoint();
        $time['start'] = Carbon::now()->format('Y-m-d H:i:s.u');
        [$headers, $content, $statusCode] = $this->processApiRequest();
        $time['stop'] = Carbon::now()->format('Y-m-d H:i:s.u');
        $this->responseStatusCode = $statusCode;
        $this->responseContentHandler($content);
        $this->responseHeaderHandler($headers);
        $this->accessLog($time, $statusCode);
        return $this;
    }

    private function assignEndpoint(): void
    {
        if ($this->isEndPointOverWrite) {
            return;
        }

        if ($this instanceof HasFinalEndPoint) {
            $this->endPoint = $this->setFinalEndPoint() . $this->appendEndPoint;
            return;
        }

        if ($this instanceof WithBaseUrl) {
            $this->baseUrl = $this->setBaseUrl();
        }

        if ($this instanceof WithApiRoute) {
            $this->apiRoute = $this->setApiRoute();
        }

        $this->endPoint = $this->baseUrl . $this->apiRoute . $this->appendEndPoint;
    }

    /**
     * @throws ResterApiException
     */
    private function processApiRequest(): array
    {
        if ($this instanceof WithRequestHeaders) {
            $this->requestHeaders = array_merge($this->defaultRequestHeaders(), $this->requestHeaders);
        }

        if ($this instanceof WithDefaultPayload) {
            $this->payloads = array_merge($this->defaultPayload(), $this->payloads);
        }

        $this->requestHeaderBeforeIntercept = $this->requestHeaders;
        if ($this instanceof RequestHeaderInterceptor) {
            $this->requestHeaders = $this->interceptRequestHeader($this->requestHeaders);
        }

        $this->payloadBeforeIntercept = $this->payloads;
        if ($this instanceof PayloadInterceptor) {
            $this->payloads = $this->interceptPayload($this->payloads);
        }

        $data = ['headers' => $this->requestHeaders];
        $data[$this->contentType->value] = $this->buildPayload();


        if (empty($this->endPoint)) {
            throw new ResterApiException('Endpoint not set');
        }

        return $this->curlInit($this->endPoint, $data, $this->method);
    }

    private function buildPayload(): array
    {
        return match ($this->contentType) {
            ContentType::JSON, ContentType::FORM_PARAMS, ContentType::BODY => $this->payloads,
            ContentType::MULTIPART => $this->buildMultipart(),
        };
    }

    private function buildMultipart(): array
    {
        return array_map(fn($key, $value) => [
            'name' => $key,
            'contents' => $value,
        ], array_keys($this->payloads), $this->payloads);
    }

    /**
     * @throws ResterApiException
     */
    private function curlInit(string $endpoint, array $data, $method): array
    {
        $responseHeader = [];
        $content = 'Rester unknown error.';
        $statusCode = 500;

        try {
            $client = new GuzzleHttp();
            $clientResponse = $client->{$method->value}($endpoint, $data);
        } catch (ClientException $e) {
            $clientResponse = $e->getResponse();
        } catch (GuzzleException $e) {
            return [$responseHeader, $e->getMessage() ?? $content, $e->getCode() ?: $statusCode];
        }

        if ($clientResponse) {
            $responseHeader = $clientResponse->getHeaders();
            $content = $clientResponse->getBody()->getContents();
            $statusCode = $clientResponse->getStatusCode();
        }

        return [$responseHeader, $content, $statusCode];
    }

    /**
     * @param $content
     * @return void
     */
    private function responseContentHandler($content): void
    {
        $this->responseContent = $content;
        $this->responseBeforeIntercept = $content;
        if ($this instanceof ResponseContentInterceptor) {
            $this->responseContent = $this->interceptResponseContent(
                $this->responseContent
            );
        }
    }

    /**
     * @param $headers
     * @return void
     */
    private function responseHeaderHandler($headers): void
    {
        $this->responseHeaders = $headers;
        $this->responseHeaderBeforeIntercept = $headers;
        if ($this instanceof ResponseHeaderInterceptor) {
            $this->responseContent = $this->interceptResponseHeader(
                $this->responseContent
            );
        }
    }

    /**
     * @param $time
     * @param $statusCode
     */
    private function accessLog($time, $statusCode): void
    {
        if (!$this->log) {
            return;
        }

        if ($this instanceof WithLogStrategy) {
            $this->logStrategy = $this->setLogStrategy();
        } else {
            $this->logStrategy = new FileLog('logs/rester_api_logs.log');
        }

        $this->loggable = [
            'uri' => $this->endPoint,
            'status_code' => $statusCode,
            'request_at' => $time['start'],
            'response_at' => $time['stop'],
        ];

        if ($this instanceof AccessLogInterceptor) {
            $this->loggable = array_merge($this->interceptAccessLog(), $this->loggable);
        }

        $this->logStrategy->log($this->loggable);
    }

    public function addPayloads(array $data = []): self
    {
        $this->payloads = array_merge($this->payloads, $data);
        return $this;
    }

    public function addHeaders(array $data = []): self
    {
        $this->requestHeaders = array_merge($this->requestHeaders, $data);
        return $this;
    }

    public function overWriteEndPoint(string $data): self
    {
        $this->isEndPointOverWrite = true;
        $this->endPoint = $data;
        return $this;
    }

    public function appendEndPoint(string $data): self
    {
        $this->appendEndPoint = $data;
        return $this;
    }

    public function assignBaseUri(string $uri): self
    {
        $this->baseUrl = $uri;
        return $this;
    }

    public function assignApiRoute(string $uri): self
    {
        $this->apiRoute = $uri;
        return $this;
    }

    /**
     * @throws ResterApiException
     */
    public function fetchBase(array $payload, array $headers)
    {
        if (!empty($payload)) {
            $this->addPayloads($payload);
        }

        if (!empty($headers)) {
            $this->addHeaders($headers);
        }

        return $this->send();
    }
}
