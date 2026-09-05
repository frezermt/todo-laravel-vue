<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Advance recurring tasks whose next recurrence date has arrived
        $this->advanceRecurringTodos($user);

        $todos = $user->todos()->latest()->get();

        return Inertia::render('Todos/Index', [
            'todos' => $todos,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'due_date' => 'nullable|date',
            'recurrence' => 'nullable|string|max:50',
            'reminder_at' => 'nullable|date',
        ]);

        // Recurring tasks do not require a specific deadline; they become due on their scheduled day.
        if (!empty($validated['recurrence']) && $validated['recurrence'] !== 'none' && empty($validated['due_date'])) {
            $validated['due_date'] = now()->toDateString();
        }

        $request->user()->todos()->create($validated);

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $todo = $request->user()->todos()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'due_date' => 'nullable|date',
            'recurrence' => 'nullable|string|max:50',
            'reminder_at' => 'nullable|date',
            'completed' => 'sometimes|boolean',
        ]);

        $wasCompleted = (bool) $todo->completed;
        $willBeCompleted = isset($validated['completed']) ? (bool) $validated['completed'] : $wasCompleted;

        // If recurring, maintain completion history on this single task
        if ($todo->recurrence && $todo->recurrence !== 'none') {
            $todayStr = now()->toDateString();
            $history = is_array($todo->completed_dates) ? $todo->completed_dates : [];

            if (!$wasCompleted && $willBeCompleted) {
                // Mark today's occurrence as completed; record in history
                if (!in_array($todayStr, $history)) {
                    $history[] = $todayStr;
                }
                $validated['completed_dates'] = $history;
                $validated['completed'] = true;
            } elseif ($wasCompleted && !$willBeCompleted) {
                // Reopen today's occurrence; remove from today's history
                $history = array_values(array_filter($history, fn($d) => $d !== $todayStr));
                $validated['completed_dates'] = $history;
                $validated['completed'] = false;
            }
        }

        $todo->update($validated);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $todo = $request->user()->todos()->find($id);

        if ($todo) {
            $todo->delete();
        }

        return redirect()->back();
    }

    /**
     * Advance recurring tasks when their scheduled recurrence date arrives.
     * Prevents multiple active occurrences and preserves completion history.
     */
    protected function advanceRecurringTodos($user): void
    {
        $today = now()->startOfDay();
        $todayStr = $today->toDateString();

        $recurringTodos = $user->todos()
            ->where('recurrence', '!=', 'none')
            ->whereNotNull('recurrence')
            ->orderBy('id', 'asc')
            ->get();

        // Prevent duplicate active tasks with same title & recurrence from old duplicate-spawning logic
        $seen = [];
        foreach ($recurringTodos as $todo) {
            $key = mb_strtolower(trim($todo->title)) . '|' . ($todo->category ?? '') . '|' . $todo->recurrence;
            if (isset($seen[$key])) {
                $existing = $seen[$key];
                // Keep the one with history or the newer one, delete duplicate
                if (!empty($todo->completed_dates) && empty($existing->completed_dates)) {
                    $existing->delete();
                    $seen[$key] = $todo;
                } else {
                    $todo->delete();
                    continue;
                }
            } else {
                $seen[$key] = $todo;
            }
        }

        // Check if scheduled recurrence date has arrived
        foreach ($seen as $todo) {
            if (!$todo->due_date) {
                $todo->update(['due_date' => $todayStr]);
                continue;
            }

            $dueDate = Carbon::parse($todo->due_date)->startOfDay();

            // When a recurring task was completed, only activate when scheduled date arrives
            if ($todo->completed) {
                $nextDueDate = $this->calculateNextDueDate($todo->recurrence, $dueDate);

                // If today has reached or passed the scheduled recurrence date, make active for new day
                if ($today->gte($nextDueDate)) {
                    // Fast-forward to current cycle if multiple intervals passed
                    while ($nextDueDate->lt($today)) {
                        $next = $this->calculateNextDueDate($todo->recurrence, $nextDueDate);
                        if ($next->lte($nextDueDate)) {
                            break;
                        }
                        if ($next->gt($today)) {
                            break;
                        }
                        $nextDueDate = $next;
                    }

                    $updateData = [
                        'completed' => false,
                        'due_date' => $nextDueDate->toDateString(),
                    ];

                    // Advance reminder if one exists (keeping original time of day)
                    if ($todo->reminder_at) {
                        $origReminder = Carbon::parse($todo->reminder_at);
                        $updateData['reminder_at'] = $nextDueDate->copy()->setTime(
                            $origReminder->hour,
                            $origReminder->minute,
                            $origReminder->second
                        );
                    }

                    $todo->update($updateData);
                }
            }
        }
    }

    /**
     * Calculate the next due date based on recurrence interval.
     */
    protected function calculateNextDueDate(string $recurrence, Carbon $baseDate): Carbon
    {
        $date = (clone $baseDate)->startOfDay();

        if ($recurrence === 'daily') {
            return $date->addDay();
        }

        if ($recurrence === 'weekdays') {
            return $date->addWeekday();
        }

        if ($recurrence === 'weekly') {
            return $date->addWeek();
        }

        if ($recurrence === 'biweekly') {
            return $date->addWeeks(2);
        }

        if ($recurrence === 'monthly') {
            return $date->addMonth();
        }

        if (str_starts_with($recurrence, 'custom:')) {
            $parts = explode(':', $recurrence);
            $count = max(1, (int) ($parts[1] ?? 1));
            $unit = $parts[2] ?? 'days';

            return match ($unit) {
                'weeks' => $date->addWeeks($count),
                'months' => $date->addMonths($count),
                default => $date->addDays($count),
            };
        }

        return $date->addDay();
    }
}
