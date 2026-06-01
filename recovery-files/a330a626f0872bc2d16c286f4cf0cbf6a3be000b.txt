<?php

namespace App\Services\Financing;

use App\Models\FinancingApplication;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Storage;

class FinancingFieldMappingService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function build(FinancingApplication $application): array
    {
        $application->loadMissing(['member.user', 'product', 'category', 'guarantors.guarantorMember']);

        $shared = $this->settings->shared();
        $cooperative = $shared['cooperative'] ?? [];
        $contact = $shared['contact'] ?? [];

        $answers = $application->custom_answers_json ?? [];

        return [
            'application' => [
                'reference_no' => $application->reference_no,
                'amount_requested' => number_format((float) $application->amount_requested, 2),
                'tenure_months' => $application->tenure_months,
                'purpose' => $application->purpose,
                'monthly_income' => $application->monthly_income !== null ? number_format((float) $application->monthly_income, 2) : null,
                'monthly_commitment' => $application->monthly_commitment !== null ? number_format((float) $application->monthly_commitment, 2) : null,
                'submitted_at' => $application->submitted_at?->format('d/m/Y'),
            ],
            'member' => [
                'name' => $application->member?->full_name ?? $application->member?->user?->name,
                'member_no' => $application->member?->member_no,
                'identity_no' => $application->member?->identity_no,
                'phone' => $application->member?->phone,
                'email' => $application->member?->email,
                'position' => $application->member?->position,
                'employer' => $application->member?->employer,
                'signature_data_url' => $application->member?->digital_signature,
            ],
            'product' => [
                'name' => $application->product?->name,
                'category' => $application->category?->name,
            ],
            'cooperative' => [
                'name' => $cooperative['name'] ?? config('app.name'),
                'registration_no' => $cooperative['registration_no'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'email' => $contact['email'] ?? null,
            ],
            'guarantors' => $application->guarantors->map(fn ($guarantor) => [
                'name' => $guarantor->guarantorMember?->full_name,
                'member_no' => $guarantor->guarantorMember?->member_no,
                'identity_no' => $guarantor->guarantorMember?->identity_no,
                'phone' => $guarantor->guarantorMember?->phone,
                'position' => $guarantor->guarantorMember?->position,
                'employer' => $guarantor->guarantorMember?->employer,
                'department' => $guarantor->guarantorMember?->department,
                'status' => $guarantor->status?->label(),
                'signature_data_url' => $this->signatureDataUrl($guarantor->signature_path),
                'consented_at' => $guarantor->consented_at?->format('d/m/Y'),
                'responded_at' => $guarantor->responded_at?->format('d/m/Y'),
                'address' => collect([
                    $guarantor->guarantorMember?->address_line_1,
                    $guarantor->guarantorMember?->address_line_2,
                    $guarantor->guarantorMember?->city,
                    $guarantor->guarantorMember?->state,
                    $guarantor->guarantorMember?->postcode,
                ])->filter()->implode(', '),
            ])->values()->all(),
            'answers' => $answers,
            'flat' => $this->flatten([
                'application' => [
                    'reference_no' => $application->reference_no,
                    'amount_requested' => number_format((float) $application->amount_requested, 2),
                    'tenure_months' => $application->tenure_months,
                    'purpose' => $application->purpose,
                ],
                'member' => [
                    'name' => $application->member?->full_name ?? $application->member?->user?->name,
                    'member_no' => $application->member?->member_no,
                    'identity_no' => $application->member?->identity_no,
                ],
                'product' => [
                    'name' => $application->product?->name,
                ],
                'answers' => $answers,
            ]),
        ];
    }

    public function replacePlaceholders(string $template, array $map): string
    {
        return preg_replace_callback('/{{\s*([A-Za-z0-9_.-]+)\s*}}/', function (array $matches) use ($map) {
            $value = data_get($map, 'flat.'.$matches[1], data_get($map, $matches[1]));

            if (is_array($value)) {
                return e(json_encode($value, JSON_UNESCAPED_UNICODE));
            }

            return e((string) ($value ?? ''));
        }, $template) ?? $template;
    }

    private function signatureDataUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
        $content = base64_encode(Storage::disk('public')->get($path));

        return "data:{$mime};base64,{$content}";
    }

    private function flatten(array $data, string $prefix = ''): array
    {
        $flat = [];

        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value) && array_is_list($value) === false) {
                $flat += $this->flatten($value, $path);
                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }
}
