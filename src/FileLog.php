<?php

namespace Itsmg\Rester;

use Illuminate\Support\Facades\Storage;
use Itsmg\Rester\Contracts\LogStrategy;

class FileLog implements LogStrategy
{
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function log(array $data): void
    {
        Storage::append($this->filePath, json_encode($data));
    }
}