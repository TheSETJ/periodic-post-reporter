<?php

use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('creates report schedule successfully', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/report-schedules', [
        'title' => 'Tehran pollution',
        'period' => 'daily',
        'keywords' => ['tehran', 'pollution'],
    ])->assertCreated()
        ->assertJsonStructure([
            'report_schedule' => [
                'id',
                'title',
                'period',
                'keywords',
                'created_at',
            ],
        ]);

    $this->assertDatabaseHas('report_schedules', [
        'user_id' => $user->id,
        'title' => 'Tehran pollution',
    ]);

    $schedule = ReportSchedule::find($response->json('report_schedule.id'));

    expect($schedule->keywords)->toBe(['tehran', 'pollution']);
});

it('fails when required params missing', function (string $missing) {
    $payload = ['title' => 'T', 'period' => 'daily', 'keywords' => ['a']];

    unset($payload[$missing]);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/report-schedules', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$missing]);
})->with(['title', 'period', 'keywords']);

it('fails with invalid values', function (array $payload, string $invalidField) {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/report-schedules', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$invalidField]);
})->with([
    'title is empty' => [['title' => '', 'period' => 'daily', 'keywords' => ['a']], 'title'],
    'title is of wrong type' => [['title' => 123, 'period' => 'daily', 'keywords' => ['a']], 'title'],
    'title exceeds max length' => [['title' => str_repeat('a', 256), 'period' => 'daily', 'keywords' => ['a']], 'title'],
    'period is empty' => [['title' => 'T', 'period' => null, 'keywords' => ['a']], 'period'],
    'period is invalid' => [['title' => 'T', 'period' => 'monthly', 'keywords' => ['a']], 'period'],
    'keywords is empty' => [['title' => 'T', 'period' => 'daily', 'keywords' => []], 'keywords'],
    'keywords is of wrong type' => [['title' => 'T', 'period' => 'daily', 'keywords' => 123], 'keywords'],
    'keywords element is of wrong type' => [['title' => 'T', 'period' => 'daily', 'keywords' => [123]], 'keywords.0'],
]);

it('rejects unauthenticated attempt', function () {
    $this->postJson('/api/v1/report-schedules', [
        'title' => 'T', 'period' => 'daily', 'keywords' => ['a'],
    ])->assertUnauthorized();
});
