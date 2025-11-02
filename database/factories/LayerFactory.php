<?php

namespace Database\Factories;

use App\Models\Layer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class LayerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Layer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lng = $this->faker->longitude(-180, 180);
        $lat = $this->faker->latitude(-90, 90);

        return [
            'name' => $this->faker->words(2, true),
            'geometry' => DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 4326)"),
        ];
    }
}
