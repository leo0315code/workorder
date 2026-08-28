<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'no' => 'TK-TEST-'.strtoupper($this->faker->unique()->bothify('####')),
            'user_id' => User::factory(),
            'subject' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'priority' => Ticket::PRIORITY_NORMAL,
            'status' => Ticket::STATUS_OPEN,
        ];
    }
}
