<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    public function index()
    {
        return view('notifications', ['notifications' => auth()->user()->notifications()->paginate(20)]);
    }

    public function read(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Open-redirect guard: notification URLs are server-generated, but a
        // tampered payload must never bounce the user to an external site.
        $url = $notification->data['url'] ?? route('dashboard');
        if (! is_string($url) || ! str_starts_with($url, '/')) {
            $url = route('dashboard');
        }

        return redirect($url);
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }
}
