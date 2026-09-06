<?php

use App\Enums\ReportPeriod;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('lists schedules ordered by created_at then id desc', function () {
    $user = User::factory()->create();

    $older = ReportSchedule::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDay()]);
    $newer = ReportSchedule::factory()->create(['user_id' => $user->id, 'created_at' => now()]);
    $newest = ReportSchedule::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

    $response = $this->actingAs($user)->getJson('/api/v1/report-schedules');

    $response->assertOk()->json();

    expect($response->json('report_schedules.0.id'))->toBe($newest->id);
    expect($response->json('report_schedules.1.id'))->toBe($newer->id);
    expect($response->json('report_schedules.2.id'))->toBe($older->id);
});

it('paginates with page and limit', function () {
    $user = User::factory()->create();

    ReportSchedule::factory()->count(10)->create(['user_id' => $user->id]);

    $page1 = $this->actingAs($user)->getJson('/api/v1/report-schedules?page=1&limit=5');
    $page2 = $this->actingAs($user)->getJson('/api/v1/report-schedules?page=2&limit=5');

    $page1->assertOk();
    $page2->assertOk();

    $ids1 = collect($page1->json('report_schedules'))->pluck('id');
    $ids2 = collect($page2->json('report_schedules'))->pluck('id');

    expect($ids1)->toHaveCount(5);
    expect($ids2)->toHaveCount(5);
    expect($ids1->intersect($ids2))->toBeEmpty();
});

it('filters by period', function () {
    $user = User::factory()->create();

    ReportSchedule::factory()->create(['user_id' => $user->id, 'period' => ReportPeriod::DAILY]);
    ReportSchedule::factory()->create(['user_id' => $user->id, 'period' => ReportPeriod::WEEKLY]);

    $response = $this->actingAs($user)->getJson('/api/v1/report-schedules?period=daily');

    $response->assertOk();

    expect($response->json('report_schedules'))->toHaveCount(1);
    expect($response->json('report_schedules.0.period'))->toBe('daily');
});

it('returns empty list for invalid period value', function () {
    $user = User::factory()->create();

    ReportSchedule::factory()->create(['user_id' => $user->id, 'period' => ReportPeriod::DAILY]);

    $this->actingAs($user)
        ->getJson('/api/v1/report-schedules?period=bogus')
        ->assertOk()
        ->assertJsonCount(0, 'report_schedules');
});

it('only lists schedules owned by the authenticated user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $ownerSchedule = ReportSchedule::factory()->create(['user_id' => $owner->id]);
    ReportSchedule::factory()->create(['user_id' => $other->id]);

    $response = $this->actingAs($owner)->getJson('/api/v1/report-schedules');

    $response->assertOk()->assertJsonCount(1, 'report_schedules');

    $ids = collect($response->json('report_schedules'))->pluck('id');

    expect($ids)->toEqual(collect([$ownerSchedule->id]));
});

it('rejects unauthenticated request', function () {
    $this->getJson('/api/v1/report-schedules')->assertUnauthorized();
});
