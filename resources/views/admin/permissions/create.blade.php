<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Access control</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Add permission</h1>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.permissions.store') }}">
        @include('admin.permissions._form', ['submitLabel' => 'Create permission'])
    </form>
</x-app-layout>
