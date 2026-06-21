<x-app-layout>
    <x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Bookings</p><h1 class="text-2xl font-bold text-[#071a3b]">Edit {{ $booking->booking_no }}</h1></div></x-slot>
    <form method="POST" action="{{ route('bookings.update', $booking) }}">@method('PUT')@include('bookings._form', ['submitLabel' => 'Save booking'])</form>
</x-app-layout>
