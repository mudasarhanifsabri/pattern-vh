<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Portfolio</p><h1 class="text-2xl font-bold text-[#071a3b]">Edit unit {{ $unit->unit_no }}</h1></div></x-slot>
    <form method="POST" action="{{ route('units.update', $unit) }}" enctype="multipart/form-data" data-single-submit>@method('PUT') @include('units._form', ['submitLabel' => 'Save unit'])</form>
</x-app-layout>
