<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDocumentChunk extends Model
{
    protected $fillable = [
        'document_name',
        'content',
    ];
}
