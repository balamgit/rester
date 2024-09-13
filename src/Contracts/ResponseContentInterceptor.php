<?php

namespace Itsmg\Rester\Contracts;

interface ResponseContentInterceptor
{
    public function interceptResponseContent($content);
}