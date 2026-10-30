@props(['tenant'])
<form method="POST" action="{{ route('admin.super-admin.tenants.destroy', $tenant) }}" x-data="{ tenantName: @js($tenant->company_name ?: $tenant->name) }" x-on:submit="if (!confirm('Delete ' + tenantName + '? Application access will stop. Records are retained for recovery.')) $event.preventDefault()">
    @csrf
    @method('DELETE')
    <x-ui.input.text name="confirmed" type="hidden" value="1" />
    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600" aria-label="Delete {{ $tenant->company_name ?: $tenant->name }}">Delete tenant</button>
</form>
