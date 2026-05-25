<div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Site Details</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <x-ui.input.text
            label="Site Name"
            name="name"
            placeholder="e.g., North Tower"
            :required="true"
            :value="old('name', $site->name ?? '')"
            :error="$errors->first('name')"
        />

        <x-ui.input.text
            label="Site Code"
            name="code"
            placeholder="e.g., NORTH-POP"
            :required="true"
            :value="old('code', $site->code ?? '')"
            :error="$errors->first('code')"
        />

        <x-ui.input.select
            label="Status"
            name="status"
            :options="['active' => 'Active', 'inactive' => 'Inactive']"
            :value="old('status', $site->status ?? 'active')"
            :required="true"
            :error="$errors->first('status')"
        />

        <x-ui.input.text
            label="Address"
            name="address"
            placeholder="Street, city, region"
            :value="old('address', $site->address ?? '')"
            :error="$errors->first('address')"
        />

        <x-ui.input.text
            type="number"
            label="Latitude"
            name="latitude"
            step="0.0000001"
            min="-90"
            max="90"
            placeholder="35.6892000"
            :required="true"
            :value="old('latitude', $site->latitude ?? '')"
            :error="$errors->first('latitude')"
        />

        <x-ui.input.text
            type="number"
            label="Longitude"
            name="longitude"
            step="0.0000001"
            min="-180"
            max="180"
            placeholder="51.3890000"
            :required="true"
            :value="old('longitude', $site->longitude ?? '')"
            :error="$errors->first('longitude')"
        />
    </div>
</div>

<div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
    <x-ui.input.textarea
        label="Description"
        name="description"
        placeholder="Operational notes, access details, or coverage summary"
        :rows="5"
        :value="old('description', $site->description ?? '')"
        :error="$errors->first('description')"
    />
</div>
