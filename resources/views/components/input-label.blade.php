@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-bold text-[#203454]']) }}>
    {{ $value ?? $slot }}
</label>
