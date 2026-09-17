<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded bg-red-600 px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-neutral-0 transition hover:bg-red-500 active:bg-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
