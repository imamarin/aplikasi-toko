<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'sale_id',
        'user_id',
        'cash_shift_id',
        'return_date',
        'total_refund',
        'refund_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'datetime',
            'total_refund' => 'decimal:2',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class);
    }

    public function details()
    {
        return $this->hasMany(SaleReturnDetail::class);
    }
}
