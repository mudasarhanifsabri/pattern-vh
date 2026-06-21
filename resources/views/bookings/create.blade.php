<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Bookings</p><h1 class="text-2xl font-bold text-[#071a3b]">Add booking</h1></div></x-slot>
    <form method="POST" action="{{ route('bookings.store') }}">@include('bookings._form', ['booking' => null, 'submitLabel' => 'Create booking'])</form>
</x-app-layout>
