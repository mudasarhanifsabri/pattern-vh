<button {{ $attributes->merge(['type' => 'submit', 'class' => 'pressable touch-target inline-flex min-h-11 items-center justify-center rounded-xl border border-transparent bg-blue-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
