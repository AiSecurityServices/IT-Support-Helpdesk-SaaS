<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** Template preview + safe test delivery (proves SMTP without business data). */
class MailTestController extends Controller
{
    public function preview(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);

        // Render the blade directly (not via Mailer::render) so previews work
        // even when the mailer is faked or unconfigured.
        return view('settings.mail-preview', [
            'html' => view('emails.test', ['workspace' => currentTenant()?->name ?? brand('product_name')])->render(),
        ]);
    }

    public function send(Request $request)
    {
        abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        $request->validate(['email' => 'nullable|email|max:255']);
        $to = $request->input('email', $request->user()->email);

        try {
            Mail::to($to)->send(new TestMail(currentTenant()?->name ?? brand('product_name')));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Test email failed: '.$e->getMessage().' Check MAIL_* settings and /admin/health.');
        }
        audit('mail.test_sent', null, ['to' => $to]);

        return back()->with('success', "Test email sent to {$to}. Delivery confirmations come from your mail provider, not this screen.");
    }
}
