<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Procurement</p><h1 class="text-2xl font-bold text-[#071a3b]">Edit {{ $vendor->company_name }}</h1></div></x-slot>
    <form method="POST" action="{{ route('vendors.update', $vendor) }}" enctype="multipart/form-data">@method('PUT')@include('vendors._form', ['submitLabel' => 'Save supplier'])</form>
</x-app-layout>
