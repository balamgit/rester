<?php

namespace Itsmg\Rester\Contracts;

interface WithLogStrategy
{
    public function setLogStrategy(): LogStrategy;
}