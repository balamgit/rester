<?php

namespace Itsmg\Rester\Contracts;

interface WithDefaultRequestHeaders
{
    public function defaultRequestHeaders(): array;
}