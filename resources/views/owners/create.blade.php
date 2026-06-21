<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Owners</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Add owner</h1>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('owners.store') }}" enctype="multipart/form-data">
        @include('owners._form', ['owner' => null, 'submitLabel' => 'Create owner'])
    </form>
</x-app-layout>
