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
        'price_silver',
        'price_gold',
        'price_platinum',
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
            'price_silver' => 'decimal:2',
            'price_gold' => 'decimal:2',
            'price_platinum' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'stock' => 'float',
            'min_stock' => 'float',
            'status' => 'boolean',
        ];
    }

    public function getPriceForLevel(?string $level): float
    {
        $normalized = strtolower(trim((string) $level));
        if ($normalized === 'silver' && !is_null($this->price_silver) && (float) $this->price_silver > 0) {
            return (float) $this->price_silver;
        }
        if ($normalized === 'gold' && !is_null($this->price_gold) && (float) $this->price_gold > 0) {
            return (float) $this->price_gold;
        }
        if ($normalized === 'platinum' && !is_null($this->price_platinum) && (float) $this->price_platinum > 0) {
            return (float) $this->price_platinum;
        }
        return (float) $this->selling_price;
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
