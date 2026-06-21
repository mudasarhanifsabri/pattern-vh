<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">{{ $config['pluralTitle'] }}</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Edit {{ $record->full_name }}</h1>
        </div>
    </x-slot>

    <form method="POST" action="{{ route($config['route'].'.update', $record) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('people._form', ['submitLabel' => 'Save '.$config['singularTitle']])
    </form>
</x-app-layout>
