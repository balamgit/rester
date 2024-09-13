<?php

namespace Itsmg\Rester\Contracts;

interface ResponseHeaderInterceptor
{
    public function interceptResponseHeader($header);
}