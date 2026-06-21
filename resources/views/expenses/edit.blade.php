<x-app-layout>
<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Expenses</p><h1 class="text-2xl font-bold text-[#071a3b]">Edit {{ $expense->expense_no }}</h1></div></x-slot>
<form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('expenses._form')</form>
</x-app-layout>
