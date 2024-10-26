<?php

namespace Itsmg\Rester;

use Illuminate\Support\Facades\DB;
use Itsmg\Rester\Contracts\LogStrategy;

class DatabaseLog implements LogStrategy
{
    protected string $connection;
    protected string $table;

    public function __construct(string $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    public function log(array $data): void
    {
        DB::connection($this->connection)
            ->table($this->table)
            ->insert($data);
    }
}