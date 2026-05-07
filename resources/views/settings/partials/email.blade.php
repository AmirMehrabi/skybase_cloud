<form action="{{ route('settings.update.email') }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    <x-ui.card title="Incoming Email" subtitle="Configure the mailbox SkyBase can connect to for received email.">
        <div class="mb-5 flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-gray-900">Incoming status</p>
                <p class="text-sm text-gray-500">Deactivate this profile without removing the saved connection details.</p>
            </div>
            <div class="flex items-center gap-3">
                <x-ui.badge :status="$emailSettings['incoming']['active'] ? 'active' : 'inactive'">
                    {{ $emailSettings['incoming']['active'] ? 'Active' : 'Inactive' }}
                </x-ui.badge>
                <x-ui.input.checkbox
                    name="incoming_active"
                    label="Active"
                    :checked="old('incoming_active', $emailSettings['incoming']['active'])"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <x-ui.input.select
                name="incoming_protocol"
                label="Protocol"
                :options="['imap' => 'IMAP', 'pop3' => 'POP3']"
                :value="old('incoming_protocol', $emailSettings['incoming']['protocol'])"
                :error="$errors->first('incoming_protocol')"
                required
            />
            <x-ui.input.text
                name="incoming_host"
                label="Host"
                :value="old('incoming_host', $emailSettings['incoming']['host'])"
                placeholder="imap.example.com"
                :error="$errors->first('incoming_host')"
            />
            <x-ui.input.text
                name="incoming_port"
                type="number"
                label="Port"
                :value="old('incoming_port', $emailSettings['incoming']['port'])"
                :error="$errors->first('incoming_port')"
            />
            <x-ui.input.select
                name="incoming_encryption"
                label="Encryption"
                :options="['ssl' => 'SSL', 'tls' => 'TLS / STARTTLS', 'none' => 'None']"
                :value="old('incoming_encryption', $emailSettings['incoming']['encryption'])"
                :error="$errors->first('incoming_encryption')"
                required
            />
            <x-ui.input.text
                name="incoming_username"
                label="Username"
                :value="old('incoming_username', $emailSettings['incoming']['username'])"
                placeholder="support@example.com"
                :error="$errors->first('incoming_username')"
            />
            <x-ui.input.password
                name="incoming_password"
                label="Password"
                placeholder="{{ filled($emailSettings['incoming']['password']) ? 'Leave blank to keep current password' : '' }}"
                :error="$errors->first('incoming_password')"
            />
            <x-ui.input.text
                name="incoming_mailbox"
                label="Mailbox"
                :value="old('incoming_mailbox', $emailSettings['incoming']['mailbox'])"
                placeholder="INBOX"
                :error="$errors->first('incoming_mailbox')"
            />
        </div>
    </x-ui.card>

    <x-ui.card title="Outgoing Email" subtitle="Configure the SMTP endpoint and sender identity for tenant email.">
        <div class="mb-5 flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-gray-900">Outgoing status</p>
                <p class="text-sm text-gray-500">Deactivate this profile without removing the saved SMTP details.</p>
            </div>
            <div class="flex items-center gap-3">
                <x-ui.badge :status="$emailSettings['outgoing']['active'] ? 'active' : 'inactive'">
                    {{ $emailSettings['outgoing']['active'] ? 'Active' : 'Inactive' }}
                </x-ui.badge>
                <x-ui.input.checkbox
                    name="outgoing_active"
                    label="Active"
                    :checked="old('outgoing_active', $emailSettings['outgoing']['active'])"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <x-ui.input.text
                name="outgoing_host"
                label="SMTP Host"
                :value="old('outgoing_host', $emailSettings['outgoing']['host'])"
                placeholder="smtp.example.com"
                :error="$errors->first('outgoing_host')"
            />
            <x-ui.input.text
                name="outgoing_port"
                type="number"
                label="SMTP Port"
                :value="old('outgoing_port', $emailSettings['outgoing']['port'])"
                :error="$errors->first('outgoing_port')"
            />
            <x-ui.input.select
                name="outgoing_encryption"
                label="Encryption"
                :options="['tls' => 'TLS / STARTTLS', 'ssl' => 'SSL', 'none' => 'None']"
                :value="old('outgoing_encryption', $emailSettings['outgoing']['encryption'])"
                :error="$errors->first('outgoing_encryption')"
                required
            />
            <x-ui.input.text
                name="outgoing_username"
                label="Username"
                :value="old('outgoing_username', $emailSettings['outgoing']['username'])"
                placeholder="mailer@example.com"
                :error="$errors->first('outgoing_username')"
            />
            <x-ui.input.password
                name="outgoing_password"
                label="Password"
                placeholder="{{ filled($emailSettings['outgoing']['password']) ? 'Leave blank to keep current password' : '' }}"
                :error="$errors->first('outgoing_password')"
            />
            <x-ui.input.text
                name="outgoing_from_email"
                label="From Email"
                :value="old('outgoing_from_email', $emailSettings['outgoing']['from_email'] ?? $tenant->email)"
                placeholder="billing@example.com"
                :error="$errors->first('outgoing_from_email')"
            />
            <x-ui.input.text
                name="outgoing_from_name"
                label="From Name"
                :value="old('outgoing_from_name', $emailSettings['outgoing']['from_name'] ?? $tenant->company_name)"
                placeholder="{{ $tenant->company_name }}"
                :error="$errors->first('outgoing_from_name')"
            />
        </div>
    </x-ui.card>

    <div class="flex flex-wrap items-center justify-end gap-3">
        <x-ui.button type="submit">
            Save Email Settings
        </x-ui.button>
    </div>
</form>

<div class="mt-4 flex flex-wrap items-center justify-end gap-3">
    <form action="{{ route('settings.test.email') }}" method="POST">
        @csrf
        <input type="hidden" name="direction" value="incoming">
        <x-ui.button type="submit" variant="secondary">
            Test Incoming
        </x-ui.button>
    </form>
    <form action="{{ route('settings.test.email') }}" method="POST">
        @csrf
        <input type="hidden" name="direction" value="outgoing">
        <x-ui.button type="submit" variant="secondary">
            Test Outgoing
        </x-ui.button>
    </form>
</div>
