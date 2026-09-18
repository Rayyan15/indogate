@if($lead->follow_up_at)
    @if($lead->isFollowUpDue())
        <span class="rounded border border-danger/20 bg-danger/10 px-1.5 py-0.5 text-[10px] font-medium text-danger">{{ __('lead.list.overdue') }}</span>
    @elseif($lead->follow_up_at->isToday())
        <span class="rounded border border-warning/20 bg-warning/10 px-1.5 py-0.5 text-[10px] font-medium text-warning">{{ __('lead.list.due_today') }}</span>
    @else
        <span class="text-[10px] text-neutral-400">{{ $lead->follow_up_at->translatedFormat('d M') }}</span>
    @endif
@else
    <span class="text-[10px] text-neutral-300">—</span>
@endif
