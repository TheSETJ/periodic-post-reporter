<?php

namespace App\Repositories\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface PostSearchRepositoryInterface
{
    public function search(array $keywords, Carbon $start, Carbon $end): Collection;
    public function countByDay(array $keywords, Carbon $start, Carbon $end): Collection;
}
