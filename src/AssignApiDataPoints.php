<?php

namespace Itsmg\Rester;

trait AssignApiDataPoints
{
    public function assignRequestData(array $data = []): self
    {
        $this->requestedData = array_merge($this->requestedData, $data);
        return $this;
    }

    public function assignRequestHeaders(array $data = []): self
    {
        $this->requestHeaders = array_merge($this->requestHeaders, $data);
        return $this;
    }

    public function assignBaseUri(string $uri): self
    {
        $this->baseUri = $uri;
        return $this;
    }

    public function assignApiRoute(string $uri): self
    {
        $this->defaultApiRoute = $uri;
        return $this;
    }
}
