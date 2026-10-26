<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_return_id',
        'product_id',
        'qty',
        'refund_price',
        'subtotal',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'refund_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
