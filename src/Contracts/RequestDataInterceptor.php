<?php

namespace Itsmg\Rester\Contracts;

interface RequestDataInterceptor
{
    public function interceptRequestData(array $request): array;
}