<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'size',
        'owner_id',
        'folder_id',
    ];

    protected $hidden = [
        'path',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
