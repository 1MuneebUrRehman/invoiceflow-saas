<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->number,
            'status' => $this->status->value,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'amount_paid' => $this->amount_paid,
            'amount_due' => $this->amountDue(),
            'issue_date' => $this->issue_date->toDateString(),
            'due_date' => $this->due_date->toDateString(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'notes' => $this->notes,
            'client' => new ClientResource($this->whenLoaded('client')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
