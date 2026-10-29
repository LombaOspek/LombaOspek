<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\ReplyTicketRequest;
use App\Models\HelpTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MentorController extends Controller
{
    public function tickets(Request $request): View
    {
        /** @var User $mentor */
        $mentor = $request->user();

        $tickets = HelpTicket::query()
            ->with(['user:id,name,nim,group_name', 'answeredBy:id,name'])
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->latest('id')
            ->paginate(12);

        return view('pages.mentor-tickets', [
            'mentor' => $mentor,
            'tickets' => $tickets,
            'openCount' => HelpTicket::query()->pending()->count(),
        ]);
    }

    public function reply(ReplyTicketRequest $request, HelpTicket $ticket): RedirectResponse
    {
        /** @var User $mentor */
        $mentor = $request->user();

        $status = TicketStatus::from($request->validated('status') ?? TicketStatus::Answered->value);

        $ticket->update([
            'reply' => $request->validated('reply'),
            'status' => $status,
            'answered_by' => $mentor->getKey(),
            'answered_at' => now(),
        ]);

        return back()->with('toast', [
            'title' => 'Balasan terkirim',
            'message' => 'Tiket "'.$ticket->subject.'" sudah dijawab.',
            'icon' => 'send',
            'tone' => 'success',
        ]);
    }
}
