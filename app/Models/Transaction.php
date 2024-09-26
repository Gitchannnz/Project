<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    // Define the fillable fields
    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'transaction_type',
        'status',
        'details',
    ];

    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define the relationship to the Order model
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

      public function getTotalAttribute()
    {
        // Assuming each order has many items and you want to sum their totals
        return $this->order ? $this->order->items->sum('price') : 0; // Adjust 'price' to the correct field name in your OrderItem model
    }
}
