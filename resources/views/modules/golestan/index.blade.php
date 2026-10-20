@extends('layouts.admin')
@section('title', 'Golestan student search')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <x-ui.card title="Golestan student search" subtitle="Shahid Bahonar University of Kerman · Student information report 1132">
        <form method="POST" action="{{ route('golestan.search') }}" class="space-y-5" x-data="{ searching: false }" @submit="searching = true">
            @csrf
            <p class="text-sm text-gray-500">Enter at least one criterion. From only = exact match; From + To = range; To only = upper limit. Criteria are combined. Fields marked کد require the university's Golestan codes. Dates use Golestan's YYYY/MM/DD format.</p>
            @error('filters')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            @foreach(collect($filterFields)->groupBy('group', preserveKeys: true) as $group => $fields)
                <details open class="rounded-lg border border-gray-200 p-4">
                    <summary class="cursor-pointer text-sm font-medium text-gray-700">{{ $group }}</summary>
                    <div class="mt-4 space-y-5">
                        @foreach($fields as $name => $field)
                            <fieldset class="space-y-1.5">
                                <legend class="text-sm font-medium text-gray-700">{{ $field['label'] }}</legend>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <x-ui.input.text :name="$name" label="From / از" :value="old($name, $filters[$name] ?? '')" :error="$errors->first($name)" :inputmode="in_array($field['type'], ['number', 'national_id']) ? 'numeric' : 'text'" :dir="$field['type'] === 'text' ? 'auto' : 'ltr'" :placeholder="$field['type'] === 'date' ? '1380/01/01' : ''" />
                                    <x-ui.input.text :name="$name.'_to'" label="To / تا" :value="old($name.'_to', $filters[$name.'_to'] ?? '')" :error="$errors->first($name.'_to')" :inputmode="in_array($field['type'], ['number', 'national_id']) ? 'numeric' : 'text'" :dir="$field['type'] === 'text' ? 'auto' : 'ltr'" :placeholder="$field['type'] === 'date' ? '1380/12/29' : ''" />
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                </details>
            @endforeach
            <x-ui.button type="submit" variant="brand" x-bind:disabled="searching"><span x-text="searching ? 'Searching…' : 'Search student'">Search student</span></x-ui.button>
        </form>
    </x-ui.card>
    @if($requestXml !== null)
        <x-ui.card title="Generated request XML" subtitle="SOAP request sent to Golestan. The password is masked in this preview.">
            <pre dir="ltr" class="max-h-96 overflow-auto rounded-lg bg-gray-50 p-4 text-xs">{{ $requestXml }}</pre>
        </x-ui.card>
    @endif
    @if($serviceError)
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $serviceError }}</div>
    @endif
    @if($result !== null)
        <x-ui.card :title="'Results ('.count($result['records']).')'">
            @forelse($result['records'] as $record)
                <dl class="grid gap-4 md:grid-cols-2">
                    @foreach($record as $field => $value)
                        <div class="border-b border-gray-100 pb-3">
                            <dt class="text-sm text-gray-500">{{ ['STDNO'=>'شماره دانشجویی', 'firstname'=>'نام', 'name'=>'نام خانوادگی', 'SSNO'=>'شماره ملی', 'FACNAME'=>'دانشکده', 'FACNO'=>'کد دانشکده', 'GRPNAME'=>'گروه آموزشی', 'GRPNO'=>'کد گروه', 'LVL'=>'مقطع', 'LVLNO'=>'کد مقطع', 'ENTRYEAR'=>'سال ورود', 'STDSTATUS'=>'وضعیت دانشجو', 'STDSTATCODE'=>'کد وضعیت', 'Passportno'=>'شماره گذرنامه'][$field] ?? $field }} <span dir="ltr">({{ $field }})</span></dt>
                            <dd class="mt-1 break-words text-sm font-medium text-gray-900" dir="auto">{{ $value !== '' ? $value : '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @unless($loop->last)<hr class="my-5 border-gray-200" />@endunless
            @empty
                <p class="text-sm text-gray-500">No student found for these criteria.</p>
            @endforelse
            <details class="mt-5">
                <summary class="cursor-pointer text-sm font-medium text-gray-700">View XML response</summary>
                <pre dir="ltr" class="mt-3 max-h-96 overflow-auto rounded-lg bg-gray-50 p-4 text-xs">{{ $result['xml'] }}</pre>
            </details>
        </x-ui.card>
    @endif
</div>
@endsection
