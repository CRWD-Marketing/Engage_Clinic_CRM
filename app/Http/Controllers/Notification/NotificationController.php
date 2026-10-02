<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Support\TopbarNotifications;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Topbar bell dropdown: new leads, new contact submissions, unread
     * WhatsApp threads and the user's own notifications. `count` is what
     * this user hasn't read yet.
     */
    public function index(Request $request)
    {
        $bell = TopbarNotifications::for($request->user());

        return response()->json([
            'count' => $bell->count(),
            'items' => $bell->items(),
        ]);
    }

    /**
     * Mark one bell row read - fired when it is clicked.
     */
    public function read(Request $request, string $id)
    {
        $bell = TopbarNotifications::for($request->user());
        $bell->markRead($id);

        return response()->json(['ok' => true, 'count' => $bell->count()]);
    }

    /**
     * "Mark all as read" - clears the badge for this user only.
     */
    public function readAll(Request $request)
    {
        $bell = TopbarNotifications::for($request->user());
        $bell->markAllRead();

        return response()->json(['ok' => true, 'count' => $bell->count()]);
    }
}
