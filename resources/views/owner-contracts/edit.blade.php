<x-app-layout>
<x-slot name="header"><div><p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Owner contracts</p><h1 class="text-2xl font-bold text-[#071a3b]">Edit {{ $contract->contract_no }}</h1></div></x-slot>
<form method="POST" action="{{ route('owner-contracts.update', $contract) }}" enctype="multipart/form-data">@method('PUT') @include('owner-contracts._form')</form>
</x-app-layout>
