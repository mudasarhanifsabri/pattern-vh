<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Access control</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Edit {{ $role->name }}</h1>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
        @method('PUT')
        @include('admin.roles._form', ['submitLabel' => 'Save role'])
    </form>

    @if ($role->name !== 'Super Admin')
        <form id="delete-role-form" method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
</x-app-layout>
