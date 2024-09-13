<?php

namespace Itsmg\Rester\Contracts;

interface RequestHeaderInterceptor
{
    public function interceptRequestHeader(array $headers): array;
}