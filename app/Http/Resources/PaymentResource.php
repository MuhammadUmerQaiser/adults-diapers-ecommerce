<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stripe_session_id' => $this->stripe_session_id,
            'stripe_payment_intent' => $this->stripe_payment_intent,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }
}
