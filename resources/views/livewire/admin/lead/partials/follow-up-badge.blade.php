@if($lead->follow_up_at)
    @if($lead->isFollowUpDue())
        <span class="inline-flex items-center gap-1 rounded bg-rose-50 border border-rose-200/80 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tight text-rose-700">
            <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span>
            <span>{{ __('lead.list.overdue') }}</span>
        </span>
    @elseif($lead->follow_up_at->isToday())
        <span class="inline-flex items-center gap-1 rounded bg-amber-50 border border-amber-200/80 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tight text-amber-800">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
            <span>{{ __('lead.list.due_today') }}</span>
        </span>
    @else
        <span class="text-[10px] text-neutral-500 font-mono">{{ $lead->follow_up_at->translatedFormat('d M') }}</span>
    @endif
@else
    <span class="text-[10px] text-neutral-300 font-mono">—</span>
@endif
