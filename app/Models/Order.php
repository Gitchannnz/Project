<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems() // This is correct; ensure you're using this name
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    // Update the getTotalAttribute to use orderItems instead of items
    public function getTotalAttribute()
    {
        return $this->orderItems->sum('price'); // Use orderItems here
    }
}

