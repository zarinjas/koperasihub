<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Cooperative;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Model;

trait InteractsWithActiveCooperative
{
    protected function activeCooperative(): ?Cooperative
    {
        return app(SettingsService::class)->activeCooperative();
    }

    protected function ensureSameCooperative(Model $model): void
    {
        abort_unless(
            $model->getAttribute('cooperative_id') && $model->getAttribute('cooperative_id') === $this->activeCooperative()?->id,
            404
        );
    }
}
