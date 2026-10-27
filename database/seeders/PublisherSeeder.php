<?php

namespace Database\Seeders;

use App\Models\Publisher;
use Illuminate\Database\Seeder;

class PublisherSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Pearson', 'McGraw-Hill', 'Wiley'] as $publisherName) {
            Publisher::query()->firstOrCreate([
                'publisher_name' => $publisherName,
            ]);
        }
    }
}
