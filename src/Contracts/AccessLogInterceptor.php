<?php

namespace Itsmg\Rester\Contracts;

interface AccessLogInterceptor
{
    public function interceptAccessLog(): array;
}