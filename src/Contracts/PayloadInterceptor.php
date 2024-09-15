<?php

namespace Itsmg\Rester\Contracts;

interface PayloadInterceptor
{
    public function interceptPayload(array $request): array;
}