<?php

namespace App\Modules\Nachhilfe\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Presentation\Resources\RoomResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::query()->latest()->get();
        return RoomResource::collection($rooms);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1'
        ]);

        $data['id'] = Str::ulid()->toString();
        $room = Room::create($data);

        return (new RoomResource($room))->response()->setStatusCode(201);
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'capacity' => 'sometimes|required|integer|min:1'
        ]);

        $room->update($data);

        return new RoomResource($room);
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return response()->noContent();
    }
}
