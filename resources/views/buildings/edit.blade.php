<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Portfolio</p><h1 class="text-2xl font-bold text-[#071a3b]">Edit {{ $building->name }}</h1></div></x-slot>
    <form method="POST" action="{{ route('buildings.update', $building) }}">@method('PUT') @include('buildings._form', ['submitLabel' => 'Save building'])</form>
</x-app-layout>
