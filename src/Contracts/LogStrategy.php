<?php

namespace Itsmg\Rester\Contracts;

interface LogStrategy
{
    public function log(array $data): void;
}