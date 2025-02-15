<?php

namespace Itsmg\Rester;

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
use Psr\Http\Message\ResponseInterface;

class Rester
{
    protected string $baseUrl = '';
    protected array $requestHeaders = [];
    protected array $payloads = [];
    protected string $endPoint = '';
    protected string $apiRoute = '';
    protected ContentType $contentType = ContentType::JSON;
    protected mixed $responseContent;
    protected ?int $responseStatusCode = null;
    protected array $responseHeaders = [];
    protected AllowedMethod $method = AllowedMethod::POST;
    protected bool $log = false;
    protected ?LogStrategy $logStrategy = null;
    protected bool $isEndPointOverWrite = false;
    protected string $appendEndPoint = '';
    protected $requestStartTime;
    protected $requestEndTime;

    public static function __callStatic($method, $arguments)
    {
        return static::new()->{$method}(...$arguments);
    }

    public static function new(): static
    {
        return new static();
    }

    public function send(): static
    {
        $this->resolveEndpoint();
        $this->mergeDefaultHeaders();
        $this->mergeDefaultPayloads();
        $this->applyInterceptors();
        $this->requestStartTime = Carbon::now()->format('Y-m-d H:i:s.u');

        try {
            $client = new GuzzleHttp();
            $response = $client->request(
                $this->method->value,
                $this->endPoint,
                $this->buildRequestOptions()
            );

            $this->handleResponse($response);
        } catch (GuzzleException $e) {
            $this->handleException($e);
        }

        $this->requestEndTime = Carbon::now()->format('Y-m-d H:i:s.u');
        $this->logRequest();
        return $this;
    }

    protected function resolveEndpoint(): void
    {
        if ($this->isEndPointOverWrite) return;

        if ($this instanceof HasFinalEndPoint) {
            $this->endPoint = $this->setFinalEndPoint();
        } else {
            $this->endPoint = $this->buildBaseEndpoint();
        }

        $this->endPoint .= $this->appendEndPoint;
    }

    protected function buildBaseEndpoint(): string
    {
        $base = $this instanceof WithBaseUrl ? $this->setBaseUrl() : $this->baseUrl;
        $route = $this instanceof WithApiRoute ? $this->setApiRoute() : $this->apiRoute;

        return rtrim($base, '/') . '/' . ltrim($route, '/');
    }

    protected function mergeDefaultHeaders(): void
    {
        if ($this instanceof WithRequestHeaders) {
            $this->requestHeaders = array_merge(
                $this->defaultRequestHeaders(),
                $this->requestHeaders
            );
        }
    }

    protected function mergeDefaultPayloads(): void
    {
        if ($this instanceof WithDefaultPayload) {
            $this->payloads = array_merge(
                $this->defaultPayload(),
                $this->payloads
            );
        }
    }

    protected function applyInterceptors(): void
    {
        if ($this instanceof RequestHeaderInterceptor) {
            $this->requestHeaders = $this->interceptRequestHeader($this->requestHeaders);
        }

        if ($this instanceof PayloadInterceptor) {
            $this->payloads = $this->interceptPayload($this->payloads);
        }
    }

    protected function buildRequestOptions(): array
    {
        $options = ['headers' => $this->requestHeaders];

        return match($this->contentType) {
            ContentType::JSON => $options + [ContentType::JSON->value => $this->payloads],
            ContentType::FORM_PARAMS => $options + [ContentType::FORM_PARAMS->value => $this->payloads],
            ContentType::BODY => $options + [ContentType::BODY->value => $this->payloads],
            ContentType::MULTIPART => $options + [ContentType::MULTIPART->value => $this->buildMultipart()],
        };
    }

    protected function buildMultipart(): array
    {
        return array_map(
            fn($key, $value) => is_array($value)
                ? $value
                : ['name' => $key, 'contents' => $value],
            array_keys($this->payloads),
            $this->payloads
        );
    }

    protected function handleResponse(ResponseInterface $response): void
    {
        $this->responseStatusCode = $response->getStatusCode();
        $this->responseHeaders = $response->getHeaders();
        $this->responseContent = $response->getBody()->getContents();

        $this->applyResponseInterceptors();
    }

    protected function applyResponseInterceptors(): void
    {
        if ($this instanceof ResponseContentInterceptor) {
            $this->responseContent = $this->interceptResponseContent($this->responseContent);
        }

        if ($this instanceof ResponseHeaderInterceptor) {
            $this->responseHeaders = $this->interceptResponseHeader($this->responseHeaders);
        }
    }

    protected function handleException(GuzzleException $e): void
    {
        if ($e instanceof ClientException && $e->hasResponse()) {
            $response = $e->getResponse();
            $this->responseStatusCode = $response->getStatusCode();
            $this->responseContent = $response->getBody()->getContents();
            return;
        }

        $this->responseStatusCode = $e->getCode() ?? 500;
        $this->responseContent = $e->getMessage();
    }

    protected function logRequest(): void
    {
        if (!$this->log) return;

        $this->logStrategy ??= $this instanceof WithLogStrategy
            ? $this->setLogStrategy()
            : new FileLog('logs/rester_api_logs-'.date('Y-m-d').'.log');

        $logData = [
            'endpoint' => $this->endPoint,
            'method' => $this->method->value,
            'status_code' => $this->responseStatusCode
        ];

        if ($this instanceof AccessLogInterceptor) {
            $logData = $this->interceptAccessLog();
        }

        $this->logStrategy->log($logData);
    }

    // Fluent interface methods
    public function withPayload(array $payload): static
    {
        $this->payloads = array_merge($this->payloads, $payload);
        return $this;
    }

    public function withHeaders(array $headers): static
    {
        $this->requestHeaders = array_merge($this->requestHeaders, $headers);
        return $this;
    }

    public function withEndpoint(string $endpoint): static
    {
        $this->isEndPointOverWrite = true;
        $this->endPoint = $endpoint;
        return $this;
    }

    public function appendEndpoint(string $path): static
    {
        $this->appendEndPoint = $path;
        return $this;
    }

    public function asJson(): static
    {
        $this->contentType = ContentType::JSON;
        return $this;
    }

    public function asMultipart(): static
    {
        $this->contentType = ContentType::MULTIPART;
        return $this;
    }

    public function withMethod(AllowedMethod $method): static
    {
        $this->method = $method;
        return $this;
    }

    // Response handling
    public function json(): array
    {
        return json_decode($this->responseContent, true) ?? [];
    }

    public function headers(): array
    {
        return $this->responseHeaders;
    }

    public function status(): int
    {
        return $this->responseStatusCode ?? 500;
    }

    public function get(): array
    {
        return [
            'content' => $this->responseContent,
            'headers' => $this->responseHeaders,
            'status_code' => $this->responseStatusCode,
        ];
    }

    public function getContent()
    {
        return $this->responseContent;
    }

    public function fetch(array $payload = [], array $headers = []): array
    {
        if (!empty($payload)) {
            $this->withPayload($payload);
        }

        if (!empty($headers)) {
            $this->withHeaders($headers);
        }

        return $this->send()->get();
    }
}