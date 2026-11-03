<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayablePaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payable_payment_id',
        'purchase_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payablePayment()
    {
        return $this->belongsTo(PayablePayment::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}

