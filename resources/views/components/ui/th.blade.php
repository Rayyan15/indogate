@props(['numeric' => false])
<th scope="col" {{ $attributes->merge(['class' => 'px-4 py-3 first:ps-5 last:pe-5 ' . ($numeric ? 'text-end' : 'text-start')]) }}>{{ $slot }}</th>
