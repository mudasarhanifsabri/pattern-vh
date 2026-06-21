<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">User management</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Edit {{ $user->name }}</h1>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @method('PUT')
        @include('admin.users._form', ['submitLabel' => 'Save changes'])
    </form>
</x-app-layout>
