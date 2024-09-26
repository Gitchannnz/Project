<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'related_id',
        'url',
        'message',
        'is_read',
        'user_id'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNotificationUrl()
    {
        switch ($this->type) {
            case 'product':
                return route('admin.product.store', ['product_slug' => $this->related_id]); 
            case 'order':

                return route('admin.order.details', ['order_id' => $this->related_id]);

            default:
                return '#'; 
        }
    }
}