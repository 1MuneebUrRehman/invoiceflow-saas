<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = Invoice::with(['client', 'items'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->client_id, fn ($q, $id) => $q->where('client_id', $id))
            ->latest()
            ->paginate(25);

        return InvoiceResource::collection($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'currency' => ['required', 'string', 'size:3'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
        ]);

        $client = Client::findOrFail($validated['client_id']);
        $taxRate = $validated['tax_rate'] ?? 0;

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'number' => 'INV-'.now()->year.'-'.str_pad((string) (Invoice::withoutGlobalScopes()->count() + 1), 4, '0', STR_PAD_LEFT),
            'status' => InvoiceStatus::Draft,
            'currency' => $validated['currency'],
            'tax_rate' => $taxRate,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $position => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'position' => $position,
            ]);
        }

        $invoice->recalculateTotals();

        return (new InvoiceResource($invoice->load(['client', 'items'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice->load(['client', 'items', 'payments']));
    }

    public function update(Request $request, Invoice $invoice): InvoiceResource
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)],
            'currency' => ['sometimes', 'string', 'size:3'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'issue_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice->update($validated);

        if (isset($validated['tax_rate'])) {
            $invoice->recalculateTotals();
        }

        return new InvoiceResource($invoice->fresh(['client', 'items']));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json(null, 204);
    }
}
