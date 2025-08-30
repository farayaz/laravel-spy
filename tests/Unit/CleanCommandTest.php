<?php

namespace Tests\Unit;

use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CleanCommandTest extends TestCase
{
    /** @test */
    public function it_can_clean_logs_older_than_specified_days()
    {
        $oldLog = HttpLog::create([
            'url' => 'https://api.example.com/old',
            'method' => 'GET',
        ]);
        $oldLog->created_at = Carbon::now()->subDays(35);
        $oldLog->save();

        $recentLog = HttpLog::create([
            'url' => 'https://api.example.com/recent',
            'method' => 'GET',
            'created_at' => Carbon::now()->subDays(5),
        ]);
        $recentLog->created_at = Carbon::now()->subDays(5);
        $recentLog->save();

        $this->artisan('spy:clean', ['--days' => 30, '--force' => true])
            ->expectsOutput('Successfully deleted 1 log(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('http_logs', [
            'url' => 'https://api.example.com/old'
        ]);

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/recent'
        ]);
    }

    /** @test */
    public function it_can_clean_logs_by_both_days_and_url()
    {
        $oldUserLog = HttpLog::create([
            'url' => 'https://api.example.com/users/1',
            'method' => 'GET',
        ]);
        $oldUserLog->created_at = Carbon::now()->subDays(35);
        $oldUserLog->save();

        $recentUserLog = HttpLog::create([
            'url' => 'https://api.example.com/users/2',
            'method' => 'GET',
        ]);
        $recentUserLog->created_at = Carbon::now()->subDays(5);
        $recentUserLog->save();

        $oldOrderLog = HttpLog::create([
            'url' => 'https://api.example.com/orders/1',
            'method' => 'GET',
        ]);

        $oldOrderLog->created_at = Carbon::now()->subDays(35);
        $oldOrderLog->save();

        $this->artisan('spy:clean', ['--days' => 30, '--url' => 'users', '--force' => true])
            ->expectsOutput('Successfully deleted 1 log(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('http_logs', [
            'url' => 'https://api.example.com/users/1'
        ]);

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/users/2'
        ]);

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/orders/1'
        ]);
    }

    /** @test */
    public function it_uses_config_clean_days_when_no_days_option_provided()
    {
        config(['spy.clean_days' => 15]);

        $testLog = HttpLog::create([
            'url' => 'https://api.example.com/test',
            'method' => 'GET',
        ]);
        $testLog->created_at = Carbon::now()->subDays(20);
        $testLog->save();

        $this->artisan('spy:clean', ['--force' => true])
            ->expectsOutput('Successfully deleted 1 log(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('http_logs', [
            'url' => 'https://api.example.com/test'
        ]);
    }

    /** @test */
    public function it_handles_no_logs_to_delete()
    {
        HttpLog::create([
            'url' => 'https://api.example.com/recent',
            'method' => 'GET',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $this->artisan('spy:clean', ['--days' => 30, '--force' => true])
            ->expectsOutput('Successfully deleted 0 log(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/recent'
        ]);
    }

    /** @test */
    public function it_requires_confirmation_in_production()
    {
        $this->app['env'] = 'production';

        $testLog = HttpLog::create([
            'url' => 'https://api.example.com/test',
            'method' => 'GET',
        ]);
        $testLog->created_at = Carbon::now()->subDays(35);
        $testLog->save();

        $this->artisan('spy:clean', ['--days' => 30])
            ->expectsQuestion('Are you sure you want to run this command?', false)
            ->assertExitCode(0);

        $this->assertDatabaseHas('http_logs', [
            'url' => 'https://api.example.com/test'
        ]);
    }
}
