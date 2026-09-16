<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRoleHistory extends Model
{
    protected $table = 'customer_role_history';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'qualified_amount' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function role(): BelongsTo { return $this->belongsTo(CustomerRole::class, 'customer_role_id'); }
    public function changedBy(): BelongsTo { return $this->belongsTo(AdminUser::class, 'changed_by_admin_user_id'); }
}
