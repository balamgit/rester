<?php

namespace Itsmg\Rester;

trait AssignApiDataPoints
{
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
}
