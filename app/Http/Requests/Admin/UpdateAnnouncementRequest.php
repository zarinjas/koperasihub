<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use App\Support\AccessControl;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends StoreAnnouncementRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_ANNOUNCEMENTS) ?? false;
    }

    public function rules(): array
    {
        /** @var Announcement|null $announcement */
        $announcement = $this->route('announcement');

        return [
            ...parent::rules(),
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::notIn(Announcement::reservedSlugs()),
                Rule::unique('announcements', 'slug')
                    ->where(fn ($query) => $query->where('cooperative_id', $announcement?->cooperative_id))
                    ->ignore($announcement?->id),
            ],
        ];
    }
}
