<?php

namespace Itsmg\Rester\Contracts;

interface WithRequestHeaders
{
    public function defaultRequestHeaders(): array;
}