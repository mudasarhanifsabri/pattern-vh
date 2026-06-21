<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Access control</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Edit {{ $permission->name }}</h1>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.permissions.update', $permission) }}">
        @method('PUT')
        @include('admin.permissions._form', ['submitLabel' => 'Save permission'])
    </form>

    <form id="delete-permission-form" method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" onsubmit="return confirm('Delete this permission?')" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</x-app-layout>
