<?php

namespace Itsmg\Rester\Contracts;

interface WithDefaultApiRoute
{
    public function defaultApiRoute(): string;
}