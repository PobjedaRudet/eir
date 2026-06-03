<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Notifications\Notification;

class ProjectApprovedNotification extends Notification
{
    public function __construct(private Project $project, private string $reviewedBy) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'         => 'project_approved',
            'project_id'   => $this->project->id,
            'project_name' => $this->project->name,
            'city_name'    => $this->project->city?->name,
            'reviewed_by'  => $this->reviewedBy,
            'message'      => "Projekat {$this->project->name} je odobren.",
        ];
    }
}
