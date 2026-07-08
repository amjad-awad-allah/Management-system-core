<?php
$namespace = "namespace App\Modules\Nachhilfe\Infrastructure\Models;\n\n";
$useModel = "use Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Concerns\HasUlids;\nuse Illuminate\Database\Eloquent\SoftDeletes;\n\n";

$models = [
    'Room' => [
        'traits' => ['HasUlids', 'SoftDeletes'],
        'table' => 'rooms',
        'fillable' => ['name', 'capacity', 'type', 'description', 'is_active']
    ],
    'CancellationPolicy' => [
        'traits' => ['HasUlids'],
        'table' => 'cancellation_policies',
        'fillable' => ['name', 'hours_before', 'deduct_percentage', 'description', 'is_active']
    ],
    'ScheduleTemplate' => [
        'traits' => ['HasUlids', 'SoftDeletes'],
        'table' => 'schedule_templates',
        'fillable' => ['teacher_id', 'room_id', 'subject_id', 'frequency', 'interval', 'start_date', 'end_date', 'days_of_week', 'start_time', 'end_time', 'is_active'],
        'casts' => "    protected function casts(): array\n    {\n        return ['days_of_week' => 'array'];\n    }\n"
    ],
    'Lesson' => [
        'traits' => ['HasUlids', 'SoftDeletes'],
        'table' => 'lessons',
        'fillable' => ['teacher_id', 'room_id', 'subject_id', 'schedule_template_id', 'type', 'date', 'start_time', 'end_time', 'duration_minutes', 'status', 'notes']
    ],
    'LessonStudent' => [
        'traits' => ['HasUlids'],
        'table' => 'lesson_students',
        'fillable' => ['lesson_id', 'student_id', 'package_id', 'hours_consumed', 'notes']
    ],
    'Attendance' => [
        'traits' => ['HasUlids'],
        'table' => 'attendances',
        'fillable' => ['lesson_student_id', 'status', 'marked_by', 'marked_at', 'note'],
        'casts' => "    protected function casts(): array\n    {\n        return ['marked_at' => 'datetime'];\n    }\n"
    ],
    'LessonConsumption' => [
        'traits' => ['HasUlids'],
        'table' => 'lesson_consumptions',
        'fillable' => ['lesson_student_id', 'package_id', 'hours_used', 'consumption_type', 'balance_before', 'balance_after', 'notes']
    ],
    'LessonStatusHistory' => [
        'traits' => ['HasUlids'],
        'table' => 'lesson_status_history',
        'fillable' => ['lesson_id', 'old_status', 'new_status', 'changed_by', 'reason', 'created_at'],
        'casts' => "    protected function casts(): array\n    {\n        return ['created_at' => 'datetime'];\n    }\n",
        'updated_at' => false
    ]
];

$dir = __DIR__ . '/app/Modules/Nachhilfe/Infrastructure/Models/';

foreach ($models as $className => $data) {
    $content = "<?php\n\n" . $namespace . $useModel;
    $content .= "class {$className} extends Model\n{\n";
    if (!empty($data['traits'])) {
        $content .= "    use " . implode(', ', $data['traits']) . ";\n\n";
    }
    
    $content .= "    protected \$table = '{$data['table']}';\n\n";
    
    $fillableStr = implode("', '", $data['fillable']);
    $content .= "    protected \$fillable = ['{$fillableStr}'];\n\n";
    
    if (isset($data['updated_at']) && $data['updated_at'] === false) {
        $content .= "    public const UPDATED_AT = null;\n\n";
    }
    
    if (isset($data['casts'])) {
        $content .= $data['casts'] . "\n";
    }
    
    // Add Relations
    if ($className === 'Lesson') {
        $content .= "    public function teacher() { return \$this->belongsTo(Teacher::class); }\n";
        $content .= "    public function room() { return \$this->belongsTo(Room::class); }\n";
        $content .= "    public function subject() { return \$this->belongsTo(Subject::class); }\n";
        $content .= "    public function scheduleTemplate() { return \$this->belongsTo(ScheduleTemplate::class); }\n";
        $content .= "    public function students() { return \$this->belongsToMany(Student::class, 'lesson_students')->withPivot('id', 'package_id', 'hours_consumed', 'notes')->withTimestamps()->using(LessonStudent::class); }\n";
        $content .= "    public function lessonStudents() { return \$this->hasMany(LessonStudent::class); }\n";
    }
    if ($className === 'LessonStudent') {
        $content .= "    public function lesson() { return \$this->belongsTo(Lesson::class); }\n";
        $content .= "    public function student() { return \$this->belongsTo(Student::class); }\n";
        $content .= "    public function package() { return \$this->belongsTo(Package::class); }\n";
        $content .= "    public function attendance() { return \$this->hasOne(Attendance::class); }\n";
    }
    if ($className === 'Attendance') {
        $content .= "    public function lessonStudent() { return \$this->belongsTo(LessonStudent::class); }\n";
    }
    if ($className === 'LessonConsumption') {
        $content .= "    public function lessonStudent() { return \$this->belongsTo(LessonStudent::class); }\n";
        $content .= "    public function package() { return \$this->belongsTo(Package::class); }\n";
    }
    
    $content .= "}\n";
    file_put_contents($dir . $className . '.php', $content);
}
echo "Models generated.\n";
