<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Notifications\Notification;

class ResourceDischargeRequiredNotification extends Notification
{
    public function __construct(
        private Project $project,
        private string $sourceType,
        private string $sourceName,
        private array $items,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $itemsPreview = collect($this->items)
            ->take(3)
            ->map(fn (array $item) => trim(($item['name'] ?? 'Stavka') . ' ' . ($item['quantity'] ?? 0) . ' ' . ($item['unit'] ?? '')))
            ->implode(', ');

        return [
            'type' => 'resource_discharge_required',
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'source_type' => $this->sourceType,
            'source_name' => $this->sourceName,
            'items_count' => count($this->items),
            'message' => "{$this->sourceType} {$this->sourceName} na projektu {$this->project->name} je razdužen. Nabavka treba razdužiti opremu: {$itemsPreview}.",
        ];
    }
}
