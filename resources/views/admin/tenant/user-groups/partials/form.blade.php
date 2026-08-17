<div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
    <div class="grid gap-6">
        <x-input.text name="name" label="Name" :value="$userGroup->name" placeholder="Reseller Company A" required />
        <x-input.textarea name="description" label="Description" :value="$userGroup->description" rows="4" placeholder="Describe the users and data assigned to this group." />
    </div>
</div>
