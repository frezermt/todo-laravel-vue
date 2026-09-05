<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
    {
        $todos = $request->user()->todos()->latest()->get();

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

        $wasCompleted = $todo->completed;
        $willBeCompleted = $validated['completed'] ?? $wasCompleted;

        // If newly marked completed and recurring, automatically spawn next occurrence
        if (!$wasCompleted && $willBeCompleted && $todo->recurrence && $todo->recurrence !== 'none') {
            $baseDate = $todo->due_date ? \Carbon\Carbon::parse($todo->due_date) : now();
            $recurrence = $todo->recurrence;
            $nextDueDate = null;

            if ($recurrence === 'daily') {
                $nextDueDate = (clone $baseDate)->addDay();
            } elseif ($recurrence === 'weekdays') {
                $nextDueDate = (clone $baseDate)->addWeekday();
            } elseif ($recurrence === 'weekly') {
                $nextDueDate = (clone $baseDate)->addWeek();
            } elseif ($recurrence === 'biweekly') {
                $nextDueDate = (clone $baseDate)->addWeeks(2);
            } elseif ($recurrence === 'monthly') {
                $nextDueDate = (clone $baseDate)->addMonth();
            } elseif (str_starts_with($recurrence, 'custom:')) {
                $parts = explode(':', $recurrence);
                $count = max(1, (int) ($parts[1] ?? 1));
                $unit = $parts[2] ?? 'days';
                $nextDueDate = match ($unit) {
                    'weeks' => (clone $baseDate)->addWeeks($count),
                    'months' => (clone $baseDate)->addMonths($count),
                    default => (clone $baseDate)->addDays($count),
                };
            }

            $nextReminder = null;
            if ($todo->reminder_at && $nextDueDate && $todo->due_date) {
                $diffInSeconds = \Carbon\Carbon::parse($todo->due_date)->diffInSeconds(\Carbon\Carbon::parse($todo->reminder_at), false);
                $nextReminder = (clone $nextDueDate)->addSeconds($diffInSeconds);
            }

            if ($nextDueDate) {
                $request->user()->todos()->create([
                    'title' => $todo->title,
                    'description' => $todo->description,
                    'category' => $todo->category,
                    'color' => $todo->color,
                    'due_date' => $nextDueDate->toDateString(),
                    'recurrence' => $todo->recurrence,
                    'reminder_at' => $nextReminder,
                    'completed' => false,
                ]);
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
}
