@extends('layouts.admin')
@section('title', App\Support\SettingsNavigation::sections()[$section]['title'])
@section('content')
<x-settings-layout :section="$section">
    @include('settings.partials.'.$section)
</x-settings-layout>
@endsection
