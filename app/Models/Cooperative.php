<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'short_name',
    'registration_no',
    'slug',
    'logo_path',
    'favicon_path',
    'primary_color',
    'secondary_color',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postcode',
    'country',
    'phone',
    'email',
    'whatsapp',
    'website_url',
    'facebook_url',
    'instagram_url',
    'linkedin_url',
    'footer_text',
    'status',
])]
class Cooperative extends Model
{
    use HasFactory, SoftDeletes;

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function pageSections(): HasMany
    {
        return $this->hasMany(PageSection::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function ansuranAgreementTemplates(): HasMany
    {
        return $this->hasMany(AnsuranAgreementTemplate::class);
    }
}