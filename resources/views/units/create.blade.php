<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Portfolio</p><h1 class="text-2xl font-bold text-[#071a3b]">Add unit</h1></div></x-slot>
    <form method="POST" action="{{ route('units.store') }}" enctype="multipart/form-data">@include('units._form', ['unit' => null, 'submitLabel' => 'Create unit'])</form>
</x-app-layout>
