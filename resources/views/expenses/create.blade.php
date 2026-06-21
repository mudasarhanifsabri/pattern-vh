<x-app-layout>
<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Expenses</p><h1 class="text-2xl font-bold text-[#071a3b]">Add expense</h1></div></x-slot>
<form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">@csrf @include('expenses._form')</form>
</x-app-layout>
