@extends('layouts.admin')
@section('title', 'Golestan student search')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <x-ui.card title="Golestan student search" subtitle="Shahid Bahonar University of Kerman · Student information report 1132">
        <form method="POST" action="{{ route('golestan.search') }}" class="space-y-5" x-data="{ searching: false }" @submit="searching = true">
            @csrf
            <div class="grid gap-5 md:grid-cols-2">
                <x-ui.input.text name="username" label="Web-service username" :value="old('username', $username)" :error="$errors->first('username')" autocomplete="off" required />
                <x-ui.input.password name="password" label="Web-service password" :error="$errors->first('password')" autocomplete="off" hint="Credentials are used only for this search and are not saved." required />
            </div>
            <x-ui.input.text name="student_number" label="Student number / شماره دانشجو" :value="old('student_number', $studentNumber)" :error="$errors->first('student_number')" placeholder="92133002" inputmode="numeric" dir="ltr" required />
            <x-ui.button type="submit" variant="brand" x-bind:disabled="searching"><span x-text="searching ? 'Searching…' : 'Search student'">Search student</span></x-ui.button>
        </form>
    </x-ui.card>
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
            @empty
                <p class="text-sm text-gray-500">No student found for this number.</p>
            @endforelse
            <details class="mt-5">
                <summary class="cursor-pointer text-sm font-medium text-gray-700">View XML response</summary>
                <pre dir="ltr" class="mt-3 max-h-96 overflow-auto rounded-lg bg-gray-50 p-4 text-xs">{{ $result['xml'] }}</pre>
            </details>
        </x-ui.card>
    @endif
</div>
@endsection
