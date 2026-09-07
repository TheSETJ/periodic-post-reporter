<?php

use App\Services\Contracts\ElasticsearchIndexManagerInterface;

beforeEach(function () {
    Storage::fake('local');
    Storage::disk('local')->put('seed-data.json', json_encode([['id' => 1, 'title' => 't']]));
});

it('fails if seed file does not exist', function () {
    $this->artisan('app:seed-elasticsearch', ['--filename' => 'missing.json'])->assertExitCode(1);
});

it('uses default file when none given', function () {
    $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);

    $manager->shouldReceive('ensureIndexExists')->once()->andReturnTrue();
    $manager->shouldReceive('bulkIndex')->once()->andReturnTrue();

    $this->app->instance(ElasticsearchIndexManagerInterface::class, $manager);

    $this->artisan('app:seed-elasticsearch')->assertExitCode(0);
});

it('fails on file read error', function () {
    Storage::shouldReceive('disk')->with('local')->andReturn(
        tap(Mockery::mock(), function ($disk) {
            $disk->shouldReceive('exists')->andReturn(true);
            $disk->shouldReceive('get')->andThrow(new \Exception('read error'));
        })
    );

    $this->artisan('app:seed-elasticsearch')->assertExitCode(1);
});

it('fails if json decode returns null', function () {
    Storage::disk('local')->put('seed-data.json', 'not valid json');

    $this->artisan('app:seed-elasticsearch')->assertExitCode(1);
});

it('fails on index creation exception', function () {
    $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);

    $manager->shouldReceive('ensureIndexExists')->once()->andReturn(false);

    $this->app->instance(ElasticsearchIndexManagerInterface::class, $manager);

    $this->artisan('app:seed-elasticsearch')->assertExitCode(1);
});

it('skips creation if index already exists', function () {
    $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);

    $manager->shouldReceive('ensureIndexExists')->once()->andReturnTrue();
    $manager->shouldReceive('bulkIndex')->once()->andReturnTrue();

    $this->app->instance(ElasticsearchIndexManagerInterface::class, $manager);

    $this->artisan('app:seed-elasticsearch')->assertExitCode(0);
});

it('fails on bulk index exception', function () {
    $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);

    $manager->shouldReceive('ensureIndexExists')->once()->andReturn(true);
    $manager->shouldReceive('bulkIndex')->once()->andReturn(false);

    $this->app->instance(ElasticsearchIndexManagerInterface::class, $manager);

    $this->artisan('app:seed-elasticsearch')->assertExitCode(1);
});

it('succeeds with valid file and no errors', function () {
    $manager = Mockery::mock(ElasticsearchIndexManagerInterface::class);

    $manager->shouldReceive('ensureIndexExists')->once()->andReturnTrue();
    $manager->shouldReceive('bulkIndex')->once()->andReturnTrue();

    $this->app->instance(ElasticsearchIndexManagerInterface::class, $manager);

    $this->artisan('app:seed-elasticsearch')->assertExitCode(0);
});
