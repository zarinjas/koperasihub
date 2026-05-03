<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use App\Support\AccessControl;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends StoreServiceRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_SERVICES) ?? false;
    }

    public function rules(): array
    {
        /** @var Service|null $service */
        $service = $this->route('service');

        return [
            ...parent::rules(),
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::notIn(Service::reservedSlugs()),
                Rule::unique('services', 'slug')
                    ->where(fn ($query) => $query->where('cooperative_id', $service?->cooperative_id))
                    ->ignore($service?->id),
            ],
        ];
    }
}
