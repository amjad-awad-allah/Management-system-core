<?php

namespace Database\Seeders\Modules\Nachhilfe;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Raum 1', 'capacity' => 1, 'type' => 'physical'],
            ['name' => 'Raum 2', 'capacity' => 1, 'type' => 'physical'],
            ['name' => 'Raum 3', 'capacity' => 5, 'type' => 'physical'],
            ['name' => 'Online', 'capacity' => 10, 'type' => 'online'],
        ];
        
        foreach ($rooms as $room) {
            $room['id'] = (string) Str::ulid();
            $room['created_at'] = now();
            $room['updated_at'] = now();
            DB::table('rooms')->insert($room);
        }
    }
}
