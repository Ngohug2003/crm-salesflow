<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\PipelineFilterData;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PipelineRepository
{
    /** @return LengthAwarePaginator<int, Pipeline> */
    public function paginateVisible(User $actor, PipelineFilterData $filters, int $perPage = 15): LengthAwarePaginator;

    public function findVisibleOrFail(User $actor, int $id): Pipeline;

    public function findVisibleForUpdateOrFail(User $actor, int $id): Pipeline;

    /** @param array<string, mixed> $data */
    public function create(array $data): Pipeline;

    /** @param array<string, mixed> $data */
    public function update(Pipeline $pipeline, array $data): Pipeline;

    public function softDelete(Pipeline $pipeline): Pipeline;

    public function resetDefaultExcept(?int $excludePipelineId = null): void;
}
