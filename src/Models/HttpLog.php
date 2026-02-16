<?php

namespace Farayaz\LaravelSpy\Models;

use Illuminate\Database\Eloquent\Model;

class HttpLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'method',
        'request_headers',
        'request_body',
        'status',
        'duration_ms',
        'response_body',
        'response_headers',
    ];

    protected $casts = [
        'request_headers' => 'json',
        'request_body' => 'json',
        'duration_ms' => 'integer',
        'response_body' => 'json',
        'response_headers' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        if (! isset($this->connection)) {
            $this->setConnection(config('spy.database_connection'));
        }

        if (! isset($this->table)) {
            $this->setTable(config('spy.table_name'));
        }

        parent::__construct($attributes);
    }

    protected function asJson($value, $flags = 0)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
