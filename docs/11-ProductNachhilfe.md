# 11 - Product Module Reference: Nachhilfe (Tutoring)

This document provides a concrete, implementation-oriented design blueprint for the **Nachhilfe (Tutoring)** business module. It serves as the developer reference implementation for both complex aggregates and simple CRUD operations in the Pragmatic Modular Monolith.

---

## 1. Directory Structure

The module resides under `app/Modules/Nachhilfe/` and adheres to the Pragmatic Clean Architecture rules:

```
app/Modules/Nachhilfe/
├── Domain/
│   ├── Entities/
│   │   ├── Student.php    <-- Plain PHP Domain Entity
│   │   ├── Teacher.php
│   │   └── Lesson.php
│   ├── ValueObjects/
│   │   └── LessonDuration.php
│   ├── Events/
│   │   └── LessonScheduled.php
│   ├── Contracts/
│   │   └── LessonRepository.php  <-- Module-specific contract belongs here
│   └── Exceptions/
│       └── TeacherUnavailableException.php
│
├── Application/
│   ├── Actions/
│   │   ├── BookLessonAction.php    <-- Complex Aggregate Action (DDD)
│   │   └── CreateSubjectAction.php <-- Simple CRUD Action (Direct Eloquent)
│   └── DTOs/
│       └── LessonBookingData.php
│
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Models/
│   │   │   ├── LessonModel.php     <-- Eloquent Model
│   │   │   └── SubjectModel.php    <-- Eloquent Model
│   │   ├── Repositories/
│   │   │   └── EloquentLessonRepository.php
│   │   └── Migrations/
│   │       ├── 2026_07_07_200000_create_lessons_table.php
│   │       └── 2026_07_07_200001_create_subjects_table.php
│   └── Providers/
│       └── NachhilfeServiceProvider.php
│
└── Presentation/
    ├── Controllers/
    │   ├── LessonController.php
    │   └── SubjectController.php
    ├── Requests/
    │   └── BookLessonRequest.php
    └── Resources/
        └── LessonResource.php
```

---

## 2. Complex Business Aggregate (DDD + Repository + Outbox)

For complex domain logic (e.g. lesson scheduling checks), we enforce strict Clean Architecture layers:

### 2.1 The Domain Entity (Plain PHP)
```php
namespace App\Modules\Nachhilfe\Domain\Entities;

use App\Modules\Nachhilfe\Domain\ValueObjects\LessonDuration;
use DateTimeImmutable;
use InvalidArgumentException;

class Lesson
{
    private string $id;
    private string $studentId;
    private string $teacherId;
    private string $subjectId;
    private DateTimeImmutable $scheduledAt;
    private LessonDuration $duration;
    private string $status;
    private float $price;
    private ?string $notes;

    public function __construct(
        string $id,
        string $studentId,
        string $teacherId,
        string $subjectId,
        DateTimeImmutable $scheduledAt,
        LessonDuration $duration,
        string $status,
        float $price,
        ?string $notes = null
    ) {
        if ($price < 0) {
            throw new InvalidArgumentException("Lesson price cannot be negative.");
        }

        $this->id = $id;
        $this->studentId = $studentId;
        $this->teacherId = $teacherId;
        $this->subjectId = $subjectId;
        $this->scheduledAt = $scheduledAt;
        $this->duration = $duration;
        $this->status = $status;
        $this->price = $price;
        $this->notes = $notes;
    }

    public function getId(): string { return $this->id; }
    public function getStudentId(): string { return $this->studentId; }
    public function getTeacherId(): string { return $this->teacherId; }
    public function getSubjectId(): string { return $this->subjectId; }
    public function getScheduledAt(): DateTimeImmutable { return $this->scheduledAt; }
    public function getDuration(): LessonDuration { return $this->duration; }
    public function getStatus(): string { return $this->status; }
    public function getPrice(): float { return $this->price; }
    public function getNotes(): ?string { return $this->notes; }

    public function cancel(): void
    {
        if ($this->status === 'completed') {
            throw new InvalidArgumentException("Completed lessons cannot be cancelled.");
        }
        $this->status = 'cancelled';
    }
}
```

### 2.2 Domain Repository Contract (Module-Specific)
```php
namespace App\Modules\Nachhilfe\Domain\Contracts;

use App\Modules\Nachhilfe\Domain\Entities\Lesson;

interface LessonRepository
{
    public function find(string $id): ?Lesson;
    public function save(Lesson $lesson): void;
    public function isTeacherAvailable(string $teacherId, \DateTimeImmutable $start, int $durationMinutes): bool;
}
```

### 2.3 Application Action (Uses Repository & Outbox)
```php
namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Application\DTOs\LessonBookingData;
use App\Modules\Nachhilfe\Domain\Entities\Lesson;
use App\Modules\Nachhilfe\Domain\ValueObjects\LessonDuration;
use App\Modules\Nachhilfe\Domain\Contracts\LessonRepository;
use App\Modules\Nachhilfe\Domain\Exceptions\TeacherUnavailableException;
use App\Modules\Nachhilfe\Domain\Events\LessonScheduled;
use App\Core\Events\Contracts\DomainEventBus; // Abstract core outbox publisher
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookLessonAction
{
    private LessonRepository $lessons;
    private DomainEventBus $eventBus;

    public function __construct(LessonRepository $lessons, DomainEventBus $eventBus)
    {
        $this->lessons = $lessons;
        $this->eventBus = $eventBus;
    }

    public function execute(LessonBookingData $dto): Lesson
    {
        return DB::transaction(function () use ($dto) {
            if (!$this->lessons->isTeacherAvailable($dto->teacherId, $dto->scheduledAt, $dto->durationMinutes)) {
                throw new TeacherUnavailableException("The teacher is unavailable.");
            }

            $lesson = new Lesson(
                Str::uuid()->toString(),
                $dto->studentId,
                $dto->teacherId,
                $dto->subjectId,
                $dto->scheduledAt,
                new LessonDuration($dto->durationMinutes),
                'scheduled',
                $dto->price,
                $dto->notes
            );

            $this->lessons->save($lesson);

            // Publish Outbox Event via abstract bus (Critical Business Event: Uses Outbox)
            $this->eventBus->publish(new LessonScheduled(
                Str::uuid()->toString(),
                $lesson->getId(),
                $lesson->getStudentId(),
                $lesson->getTeacherId()
            ));

            return $lesson;
        });
    }
}
```

---

## 3. Simple CRUD Entity (Bypassing Repositories & Outbox)

For simple CRUD domains (e.g. subjects mapping), we query and write directly to the Eloquent Model inside the Action to avoid unnecessary mapping boilerplate, and dispatch events using native Laravel events:

### 3.1 Simple Application Action (Direct Eloquent Queries & Native Events)
```php
namespace App\Modules\Nachhilfe\Application\Actions;

use App\Modules\Nachhilfe\Infrastructure\Persistence\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Persistence\Events\SubjectCreated; // Native Laravel Event
use Illuminate\Support\Str;

class CreateSubjectAction
{
    public function execute(array $data): SubjectModel
    {
        // CRUD entities bypass Domain Entities & Repositories, querying Eloquent directly
        $subject = SubjectModel::create([
            'id'          => Str::uuid()->toString(),
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $data['is_active'] ?? true,
        ]);

        // Simple CRUD event: Dispatches via native Laravel Events (No Outbox)
        event(new SubjectCreated($subject->id, $subject->name));

        return $subject;
    }
}
```
