<?php

namespace Tests;

use App\Core\Models\User;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * @property User $user
 * @property Teacher $teacher
 * @property Room $room
 * @property SubjectModel $subject
 */
abstract class TestCase extends BaseTestCase
{
    //
}
