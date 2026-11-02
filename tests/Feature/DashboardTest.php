<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Farayaz\LaravelSpy\Models\HttpLog;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['spy.dashboard.enabled' => true]);
        config(['spy.dashboard.middleware' => []]);
        require __DIR__ . '/../../routes/web.php';
    }

    /** @test */
    public function it_filters_logs_and_shows_details(): void
    {
        $matching = HttpLog::create([
            'url' => 'https://api.example.com/orders', 'method' => 'POST', 'status' => 503,
            'duration_ms' => 1200, 'request_body' => ['id' => 42],
        ]);
        HttpLog::create([
            'url' => 'https://api.example.com/users', 'method' => 'GET', 'status' => 200,
            'duration_ms' => 20,
        ]);

        $this->get('/spy/logs?url=orders&method=POST&status=5xx&min_duration=1000')
            ->assertOk()->assertSee('orders')->assertDontSee('users');

        $this->get('/spy/logs/' . $matching->id)
            ->assertOk()->assertSee('orders')->assertSee('42');
    }

    /** @test */
    public function it_can_show_logs_outside_the_default_period(): void
    {
        $old = HttpLog::create(['url' => 'https://old.example.com', 'method' => 'GET']);
        $old->created_at = CarbonImmutable::now()->subMonths(6);
        $old->save();

        $this->get('/spy/logs')->assertOk()->assertDontSee('old.example.com');
        $this->get('/spy/logs?period=all')->assertOk()->assertSee('old.example.com');
    }

    /** @test */
    public function it_serves_the_chart_script_locally(): void
    {
        $version = substr(hash_file('sha256', __DIR__ . '/../../resources/assets/apexcharts.min.js'), 0, 12);
        $this->get('/spy')->assertOk()
            ->assertSee('/spy/assets/apexcharts.min.js?v=' . $version)
            ->assertDontSee('cdn.jsdelivr.net')
            ->assertDontSee('fonts.bunny.net');

        $asset = $this->get('/spy/assets/apexcharts.min.js')->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $this->assertStringContainsString('immutable', $asset->headers->get('Cache-Control'));
    }

    /** @test */
    public function dashboard_users_can_open_log_details_without_a_second_permission(): void
    {
        $log = HttpLog::create([
            'url' => 'https://example.test', 'method' => 'GET',
            'request_headers' => ['Authorization' => ['Bearer secret']],
        ]);

        $this->get('/spy/logs')->assertOk()->assertSee('/spy/logs/' . $log->id);
        $this->get('/spy/logs/' . $log->id)->assertOk()->assertSee('Bearer secret');
    }

    /** @test */
    public function non_numeric_log_ids_return_not_found(): void
    {
        $this->get('/spy/logs/not-a-number')->assertNotFound();
    }

    /** @test */
    public function url_filter_matches_literal_like_wildcards(): void
    {
        HttpLog::create(['url' => 'https://example.test/a%b_c!d', 'method' => 'GET']);
        HttpLog::create(['url' => 'https://example.test/axb-c!d', 'method' => 'GET']);

        $this->get('/spy/logs?url=' . rawurlencode('a%b_c!d'))->assertOk()
            ->assertSee('a%b_c!d')->assertDontSee('axb-c!d');
    }

    /** @test */
    public function it_orders_by_log_time_and_paginates(): void
    {
        $older = HttpLog::create(['url' => 'https://oldest.example.test', 'method' => 'GET']);
        $newer = HttpLog::create(['url' => 'https://newest.example.test', 'method' => 'GET']);
        $older->created_at = CarbonImmutable::now()->subHours(2);
        $older->save();
        $newer->created_at = CarbonImmutable::now()->subHour();
        $newer->save();

        $page = $this->get('/spy/logs?sort=newest&per_page=10')->assertOk();
        $this->assertTrue(strpos($page->getContent(), 'newest.example.test') < strpos($page->getContent(), 'oldest.example.test'));
        $page->assertSee('10 per page')->assertSee('Page 1 of 1');
    }

    /** @test */
    public function logs_page_does_not_run_analytics_queries(): void
    {
        HttpLog::create(['url' => 'https://example.test', 'method' => 'GET']);
        $queries = [];
        DB::connection('testing')->listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->get('/spy/logs?period=all')->assertOk();

        $this->assertCount(2, $queries);
        $this->assertStringNotContainsString('group by', implode(' ', $queries));
    }

    /** @test */
    public function invalid_period_defaults_to_24_hours(): void
    {
        $old = HttpLog::create(['url' => 'https://old.example.test', 'method' => 'GET']);
        $old->created_at = CarbonImmutable::now()->subDays(2);
        $old->save();

        $this->get('/spy/logs?period=invalid')->assertOk()
            ->assertDontSee('old.example.test')
            ->assertSee('value="24h" selected', false)
            ->assertSee('/spy?period=24h', false);
    }
}
