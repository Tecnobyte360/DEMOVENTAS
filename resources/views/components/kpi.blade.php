@props([
    'label' => 'KPI',
    'value' => 0,
    'money' => false,
    'color' => 'slate', // slate | indigo | emerald | rose | amber | blue
])

@php
    $map = [
        'slate'   => 'text-gray-900 dark:text-white',
        'indigo'  => 'text-indigo-600 dark:text-indigo-400',
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        'rose'    => 'text-rose-600 dark:text-rose-400',
        'amber'   => 'text-amber-600 dark:text-amber-400',
        'blue'    => 'text-blue-600 dark:text-blue-400',
    ];

    $valueClass = $map[$color] ?? $map['slate'];

    $display = $money
        ? '$' . number_format((float)$value, 0, ',', '.')
        : number_format((float)$value, 0, ',', '.');
@endphp

<div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
    <div class="text-lg md:text-xl font-extrabold {{ $valueClass }}">
        {{ $display }}
    </div>
</div>
