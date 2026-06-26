@php
    $selectedPermissions = old('permissions', $role->permissions ?? []);
    $hasFullAccess = in_array('*', $selectedPermissions, true);
@endphp

<div class="space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-gray-700">نام نقش <span class="text-red-500">*</span></label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $role->name) }}"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm font-medium text-gray-700">توضیح</label>
                <input
                    id="description"
                    name="description"
                    type="text"
                    value="{{ old('description', $role->description) }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm" x-data="{ fullAccess: @js($hasFullAccess) }">
        <div class="border-b border-gray-200 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">دسترسی ها</h2>
                    <p class="mt-1 text-sm text-gray-600">برای هر بخش، نوع دسترسی مورد نیاز این نقش را انتخاب کنید.</p>
                </div>
                <label class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="permissions[]" value="*" x-model="fullAccess" class="h-4 w-4 rounded border-gray-300 text-blue-600">
                    دسترسی کامل
                </label>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">بخش</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">مشاهده</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">ایجاد و ویرایش</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">حذف</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">عملیات ویژه</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($modules as $moduleKey => $module)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900">{{ $module['label'] }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $module['description'] }}</div>
                            </td>
                            @foreach(['read', 'write', 'delete', 'actions'] as $action)
                                @php($permission = "{$moduleKey}.{$action}")
                                <td class="px-4 py-4 text-center">
                                    @if(array_key_exists($action, $module['actions']))
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission }}"
                                            @checked($hasFullAccess || in_array($permission, $selectedPermissions, true))
                                            :disabled="fullAccess"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 disabled:opacity-40"
                                        >
                                    @else
                                        <span class="text-xs text-gray-300">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
