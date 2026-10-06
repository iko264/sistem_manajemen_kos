<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $type = fake()->randomElement(Room::TYPES);

        return [
            'number' => (string) fake()->unique()->numberBetween(100, 999),
            'type' => $type,
            'price' => $type === 'deluxe' ? 1200000 : 750000,
            'status' => Room::STATUS_AVAILABLE,
            'description' => fake()->sentence(),
        ];
    }

    public function occupied(): static
    {
        return $this->state(fn () => ['status' => Room::STATUS_OCCUPIED]);
    }

    public function maintenance(): static
    {
        return $this->state(fn () => ['status' => Room::STATUS_MAINTENANCE]);
    }
}
