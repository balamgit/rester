<?php

namespace Itsmg\Rester;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client as GuzzleHttp;
use GuzzleHttp\Exception\GuzzleException;
use Itsmg\Rester\Contracts\AccessLogInterceptor;
use Itsmg\Rester\Contracts\HasFinalEndPoint;
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

trait BaseFetchDna
{
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

    public function assignEndpoint()
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
    protected function processApiRequest(): array
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

        $data = [
            'headers' => $this->requestHeaders,
            'json' => $this->payloads,
        ];

        if (empty($this->endPoint)) {
            throw new ResterApiException('Endpoint not set');
        }

        return $this->curlInit($this->endPoint, $data, $this->method);
    }

    /**
     * @throws ResterApiException
     */
    protected function curlInit(string $endpoint, array $data, $method = 'post'): array
    {
        $responseHeader = '';
        $method = strtolower($method);

        if (!in_array($method, ['post', 'put', 'delete', 'get', 'patch'])) {
            throw new ResterApiException('Unknown HTTP method ' . $method);
        }

        try {
            $client = new GuzzleHttp();
            $clientResponse = $client->{$method}($endpoint, $data);
            $responseHeader = $clientResponse->getHeaders();
            $content = $clientResponse->getBody()->getContents();
            $statusCode = $clientResponse->getStatusCode();
        } catch (GuzzleException|Exception $e) {
            $content = $e->getMessage() ?? 'Rester API unknown error.';
            $statusCode = $e->getCode() ?? 500;
        }

        return [$responseHeader, $content, $statusCode];
    }

    /**
     * @param $content
     * @return void
     */
    public function responseContentHandler($content): void
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
    public function responseHeaderHandler($headers): void
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
    public function accessLog($time, $statusCode)
    {
        if (!$this->log) {
            return;
        }

        if ($this instanceof WithLogStrategy) {
            $this->logStrategy = $this->setLogStrategy();
        }

        if (!($this instanceof WithLogStrategy)) {
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
}
