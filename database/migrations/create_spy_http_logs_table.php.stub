<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection(config('spy.connection'))
            ->create(config('spy.table_name'), function (Blueprint $table) {
                $table->id();
                $table->string('url', 2048);
                $table->string('method', 6)->index();
                $table->json('request_headers')->nullable();
                $table->json('request_body')->nullable();
                $table->unsignedSmallInteger('status')->nullable()->index();
                $table->json('response_body')->nullable();
                $table->json('response_headers')->nullable();
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::connection(config('spy.connection'))
            ->dropIfExists(config('spy.table_name'));
    }
};
