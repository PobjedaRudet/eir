<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Notifications\Notification;

class ProjectSubmittedNotification extends Notification
{
    public function __construct(private Project $project) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'         => 'project_submitted',
            'project_id'   => $this->project->id,
            'project_name' => $this->project->name,
            'city_name'    => $this->project->city?->name,
            'submitted_by' => $this->project->leader?->name,
            'message'      => "Vođa {$this->project->leader?->name} je podnio projekat {$this->project->name} na odobrenje.",
        ];
    }
}
