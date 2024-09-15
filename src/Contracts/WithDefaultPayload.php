<?php

namespace Itsmg\Rester\Contracts;

interface WithDefaultPayload
{
    public function defaultPayload(): array;
}