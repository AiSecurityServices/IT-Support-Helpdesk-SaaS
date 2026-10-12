@php
$statusColors = ['new'=>'blue','open'=>'green','pending_customer'=>'yellow','pending_internal'=>'purple','resolved'=>'slate','closed'=>'slate'];
$priorityColors = ['low'=>'slate','normal'=>'blue','high'=>'yellow','urgent'=>'red'];
$ticket = $ticket ?? ($t ?? null);
@endphp
@if($ticket)
<x-badge :color="$statusColors[$ticket->status] ?? 'slate'">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</x-badge>
<x-badge :color="$priorityColors[$ticket->priority] ?? 'slate'">{{ ucfirst($ticket->priority) }}</x-badge>
@if($ticket->isOverdue())<x-badge color="red">Overdue</x-badge>@endif
@if($ticket->escalation_level > 0)<x-badge color="purple">Esc {{ $ticket->escalation_level }}</x-badge>@endif
@endif
