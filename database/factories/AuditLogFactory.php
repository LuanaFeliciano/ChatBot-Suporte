<?php

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'action' => fake()->randomElement(AuditAction::cases()),
            'entity_type' => AuditEntityType::Document,
            'entity_id' => fake()->numberBetween(1, 100),
            'payload' => ['name' => fake()->words(3, true)],
            'performed_by' => fake()->userName(),
        ];
    }
}
