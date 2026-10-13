@props(['subscription'])
<div x-data="{ password: null, currentPassword: '', error: '', async reveal() {
    this.error = ''; this.password = null;
    const response = await fetch(@js(route('subscriptions.credentials', $subscription)), {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
        body: JSON.stringify({ current_password: this.currentPassword })
    });
    this.currentPassword = '';
    const data = await response.json();
    if (response.ok) { this.password = data.password; setTimeout(() => { this.password = null }, 30000); }
    else { this.error = data.message || 'Unable to reveal credentials.'; }
} }">
    <form @submit.prevent="reveal()">
        <x-input.password id="credential-current-password" name="current_password" label="Confirm your password to reveal the PPP password" xModel="currentPassword" required autocomplete="current-password" />
        <x-ui.button type="submit">Reveal PPP password</x-ui.button>
    </form>
    <p x-text="error" role="alert"></p>
    <code x-text="password" x-show="password !== null" x-cloak></code>
</div>
