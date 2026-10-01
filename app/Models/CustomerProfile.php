<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    public const LEGAL_TYPE_INDIVIDUAL = 'individual';
    public const LEGAL_TYPE_IP = 'individual_entrepreneur';
    public const LEGAL_TYPE_ENTITY = 'legal_entity';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'fns_checked_at' => 'datetime',
            'fns_data' => 'array',
            'fns_check_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
