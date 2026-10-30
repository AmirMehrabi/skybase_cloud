@props(['invoices'])
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th class="p-3">Invoice</th><th>Package</th><th>Amount</th><th>Status</th><th>Created</th></tr></thead><tbody>
@forelse($invoices as $invoice)
<tr class="border-t border-gray-200"><td class="p-3">#{{ $invoice->id }}</td><td>{{ ucfirst($invoice->package_key) }}</td><td>{{ $invoice->currency }} {{ number_format($invoice->amount_minor / 100, 2) }}</td><td>{{ $invoice->status }} {{ $invoice->simulated ? '(simulated)' : '' }}</td><td>{{ $invoice->created_at->format('Y-m-d') }}</td></tr>
@empty<tr><td class="p-3" colspan="5">No software invoices.</td></tr>@endforelse
</tbody></table></div>
