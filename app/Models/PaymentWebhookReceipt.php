<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookReceipt extends Model
{
    protected $fillable = ['order_id', 'payload_hash', 'payload', 'processed_at', 'error'];

    protected $casts = ['payload' => 'array', 'processed_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
