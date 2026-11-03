<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivablePaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'receivable_payment_id',
        'sale_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function receivablePayment()
    {
        return $this->belongsTo(ReceivablePayment::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}

