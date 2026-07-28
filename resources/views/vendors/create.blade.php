<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Procurement</p><h1 class="text-2xl font-bold text-[#071a3b]">Register vendor / supplier</h1></div></x-slot>
    <form method="POST" action="{{ route('vendors.store') }}" enctype="multipart/form-data">@include('vendors._form', ['submitLabel' => 'Register vendor'])</form>
</x-app-layout>
