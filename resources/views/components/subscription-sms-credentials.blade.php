@props(['subscription'])
@if(!config('security.password_encryption', false) && auth()->user()->hasPermission('subscriptions.credentials'))
<details class="mt-3 rounded-lg border border-gray-200 p-3"><summary class="cursor-pointer text-sm font-medium text-teal-800">Send credentials by SMS</summary>
<p class="my-3 text-xs text-gray-500">Confirm your password to send the current PPP credentials to the customer's saved phone. Gateway charges may apply.</p>
<form action="{{ route('subscriptions.credentials-sms', $subscription) }}" method="POST" class="space-y-3">@csrf<x-ui.input.password name="current_password" :id="'sms-confirm-'.$subscription->id" label="Your password" required autocomplete="current-password" /><x-ui.button type="submit" variant="secondary">Queue credentials SMS</x-ui.button></form>
</details>
@endif
