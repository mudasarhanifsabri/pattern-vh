<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">{{ $config['pluralTitle'] }}</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Add {{ $config['singularTitle'] }}</h1>
        </div>
    </x-slot>

    <form method="POST" action="{{ route($config['route'].'.store') }}" enctype="multipart/form-data">
        @include('people._form', ['submitLabel' => 'Create '.$config['singularTitle']])
    </form>
</x-app-layout>
