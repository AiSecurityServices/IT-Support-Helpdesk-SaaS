<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /** Authorised private-file downloads (customers only see public files on their records). */
    public function download(Attachment $attachment)
    {
        $user = auth()->user();
        abort_unless((int) $attachment->tenant_id === (int) $user->tenant_id, 404);
        abort_unless(Storage::disk('private')->exists($attachment->stored_path), 404);

        if ($user->isCustomer()) {
            abort_unless($attachment->visibility === 'public', 403);
            abort_unless($this->customerCanAccess($user, $attachment), 403);
        }

        audit('attachment.downloaded', $attachment);

        return Storage::disk('private')->download($attachment->stored_path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type ?? 'application/octet-stream',
        ]);
    }

    protected function customerCanAccess($user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;
        if ($attachable instanceof TicketMessage) {
            $ticket = $attachable->ticket;

            return $ticket && (int) $ticket->customer_id === (int) $user->customer_id;
        }
        if ($attachable instanceof Ticket) {
            return (int) $attachable->customer_id === (int) $user->customer_id;
        }

        return false;
    }
}
