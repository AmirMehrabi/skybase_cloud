<div class="space-y-6">
    <x-settings-section title="USW connection" description="Enter the client credentials issued for your organization. Both sign-in methods start disabled.">
        <dl class="mb-6 space-y-3 text-sm text-slate-700">
            @foreach ([
                'USW login URL' => \App\Services\GovernmentSso::publicUrl('government-sso.entry'),
                'Staff sign-in URL' => \App\Services\GovernmentSso::publicUrl('auth.login', ['sso_tenant' => $tenant->slug]),
                'Customer sign-in URL' => \App\Services\GovernmentSso::publicUrl('customer.login', ['sso_tenant' => $tenant->slug]),
                'Callback URL' => \App\Services\GovernmentSso::publicUrl('government-sso.callback'),
                'Staff landing URL' => \App\Services\GovernmentSso::publicUrl('dashboard'),
                'Customer landing URL' => \App\Services\GovernmentSso::publicUrl('customer.dashboard'),
            ] as $label => $url)
                <div>
                    <dt class="font-semibold">{{ $label }}</dt>
                    <dd class="break-all">{{ $url }}</dd>
                </div>
            @endforeach
        </dl>
        <form method="POST" action="{{ route('settings.update.government-sso') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid gap-4 md:grid-cols-2">
                <x-input.text name="client_id" label="Client ID" :value="$settings['client_id']" />
                <x-input.text name="client_secret" type="password" label="Client secret" :placeholder="$hasSecret ? 'Saved. Enter a new value to replace it.' : null" />
            </div>
            <x-input.text name="label" label="Sign-in button label" :value="$settings['label']" :required="true" />
            <div class="grid gap-4 md:grid-cols-2">
                @foreach (['authorize_path' => 'Authorization path', 'token_path' => 'Token path', 'userinfo_path' => 'UserInfo path', 'logout_path' => 'Logout path'] as $name => $label)
                    <x-input.text :name="$name" :label="$label" :value="$settings[$name]" :required="true" />
                @endforeach
            </div>
            <x-input.select name="token_format" label="Token request format" :options="['form' => 'URL-encoded form', 'multipart' => 'Multipart form']" :value="$settings['token_format']" :required="true" />
            <x-input.checkbox name="pkce" label="Use S256 PKCE" help="Enable when USW confirms support for your client." :checked="$settings['pkce']" />
            <x-input.checkbox name="web_enabled" label="Enable staff sign-in" :checked="$settings['web_enabled']" />
            <x-input.checkbox name="customer_enabled" label="Enable customer portal sign-in" :checked="$settings['customer_enabled']" />
            <button class="rounded-lg bg-teal-800 px-4 py-2 font-semibold text-white hover:bg-teal-900">Save settings</button>
        </form>
    </x-settings-section>

    <x-settings-section title="Account enrollment" description="Use an existing account’s email. Verify the person’s national ID before enrolling it.">
        <form method="POST" action="{{ route('settings.government-sso.identity') }}" class="space-y-4">
            @csrf
            <x-input.select name="account_type" label="Account type" :options="['web' => 'Staff', 'customer' => 'Customer']" :required="true" />
            <x-input.email name="account_email" label="Account email" :required="true" />
            <x-input.text name="government_national_id" label="National ID (required for enrollment)" />
            <div class="flex flex-wrap gap-3">
                <button name="operation" value="enroll" class="rounded-lg bg-teal-800 px-4 py-2 font-semibold text-white hover:bg-teal-900">Enroll or replace identity</button>
                <button name="operation" value="revoke" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-50">Revoke identity</button>
            </div>
        </form>
    </x-settings-section>
</div>
