<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'unit_id',
        'sku',
        'barcode',
        'name',
        'cost_price',
        'selling_price',
        'stock',
        'min_stock',
        'image',
        'status',
        'discount_type',
        'discount_value',
    ];

    protected $appends = [
        'has_discount',
        'unit_discount',
        'final_price',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'stock' => 'float',
            'min_stock' => 'float',
            'status' => 'boolean',
        ];
    }

    public function getHasDiscountAttribute(): bool
    {
        return (float) $this->discount_value > 0;
    }

    public function getUnitDiscountAttribute(): float
    {
        if (!$this->has_discount) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            return round(((float) $this->selling_price * (float) $this->discount_value) / 100, 2);
        }

        return min((float) $this->selling_price, (float) $this->discount_value);
    }

    public function getFinalPriceAttribute(): float
    {
        return max(0, (float) $this->selling_price - $this->unit_discount);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= min_stock');
    }

    public function getProfitMarginAttribute()
    {
        return $this->selling_price - $this->cost_price;
    }
}
