<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\Program;
use App\Models\ProgramRsvp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProgramRsvpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ProgramRsvp $rsvp,
        private readonly Member $member,
        private readonly Program $program,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $responseLabels = [
            'hadir' => 'Hadir',
            'tidak_hadir' => 'Tidak Hadir',
            'mungkin' => 'Mungkin',
        ];

        $response = $responseLabels[$this->rsvp->response] ?? $this->rsvp->response;

        return [
            'program_id' => $this->program->id,
            'program_title' => $this->program->title,
            'member_id' => $this->member->id,
            'member_name' => $this->member->full_name,
            'member_no' => $this->member->member_no,
            'response' => $this->rsvp->response,
            'response_label' => $response,
            'title' => 'Respon Program: ' . $this->program->title,
            'summary' => "{$this->member->full_name} ({$this->member->member_no}) - {$response}",
            'url' => route('admin.programs.attendance', $this->program),
        ];
    }
}
