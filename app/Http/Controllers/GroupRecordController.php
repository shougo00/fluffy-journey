<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Record;
use App\Models\Shot;
use App\Models\Lineup;
use App\Models\LineupMember;

class GroupRecordController extends Controller
{
    public function index(Request $request, $groupId)
    {
        $this->checkGroupAccess($groupId);

        $group = Group::with('users')->findOrFail($groupId);
        $date = $request->date ?? date('Y-m-d');

        $lineup = Lineup::with('members.user')
            ->where('group_id', $groupId)
            ->where('date', $date)
            ->first();

        $tateSize = 3;
        $lineupSlots = collect();
        $users = collect();

        if ($lineup) {
            $this->syncLineupMembers($lineup, $group);

            $lineup = Lineup::with('members.user')->findOrFail($lineup->id);
            $tateSize = $lineup->tate_size;

            $placedMembers = $lineup->members
                ->where('is_absent', false)
                ->filter(fn($m) => !is_null($m->position))
                ->sortBy('position')
                ->values();

            $users = $placedMembers->pluck('user')->filter()->values();

            $maxPosition = $placedMembers->max('position') ?? 0;

            if ($maxPosition > 0) {
                $totalSlots = ceil($maxPosition / $tateSize) * $tateSize;

                for ($pos = 1; $pos <= $totalSlots; $pos++) {
                    $member = $placedMembers->firstWhere('position', $pos);

                    $lineupSlots->push((object)[
                        'position' => $pos,
                        'member' => $member,
                        'user' => $member?->user,
                        'is_empty' => is_null($member),
                    ]);
                }
            }
        }

        $userIds = $users->pluck('id');

        $tates = Record::whereIn('user_id', $userIds)
            ->where('date', $date)
            ->where('practice_type', 'official')
            ->pluck('tate_no')
            ->unique()
            ->sort()
            ->values();

        if ($users->isNotEmpty() && $tates->isNotEmpty()) {
            foreach ($tates as $tateNo) {
                foreach ($users as $user) {
                    $this->ensureRecordWithShots($user->id, $date, $tateNo);
                }
            }
        }

        $records = Record::with('shots')
            ->whereIn('user_id', $userIds)
            ->where('date', $date)
            ->where('practice_type', 'official')
            ->get()
            ->groupBy('user_id');

        $hitCounts = [];

        foreach ($users as $user) {
            $hitCounts[$user->id] = 0;

            if (isset($records[$user->id])) {
                foreach ($records[$user->id] as $record) {
                    $hitCounts[$user->id] += $record->shots
                        ->where('result', 'hit')
                        ->count();
                }
            }
        }
        $month = $request->month ?? \Carbon\Carbon::parse($date)->format('Y-m');

        $prevMonth = \Carbon\Carbon::parse($month . '-01')->subMonth()->format('Y-m');
        $nextMonth = \Carbon\Carbon::parse($month . '-01')->addMonth()->format('Y-m');

        $lineupDates = Lineup::where('group_id', $groupId)
            ->whereYear('date', \Carbon\Carbon::parse($month . '-01')->year)
            ->whereMonth('date', \Carbon\Carbon::parse($month . '-01')->month)
            ->whereHas('members', function ($q) {
                $q->where('is_absent', false)
                ->whereNotNull('position');
            })
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        return view('group.records', compact(
            'group',
            'records',
            'tates',
            'date',
            'users',
            'hitCounts',
            'tateSize',
            'month',
            'prevMonth',
            'nextMonth',
            'lineupDates',
            'lineupSlots'
        ));
    }

    public function addTate(Request $request, $groupId)
    {
        $this->checkGroupAccess($groupId);

        $group = Group::with('users')->findOrFail($groupId);
        $date = $request->date ?? date('Y-m-d');

        $lineup = Lineup::with('members.user')
            ->where('group_id', $groupId)
            ->where('date', $date)
            ->first();

        if (!$lineup) {
            return redirect("/group/{$groupId}/records?date={$date}");
        }

        $this->syncLineupMembers($lineup, $group);

        $lineup = Lineup::with('members.user')->findOrFail($lineup->id);

        $users = $lineup->members
            ->where('is_absent', false)
            ->filter(fn($m) => !is_null($m->position))
            ->sortBy('position')
            ->pluck('user')
            ->filter()
            ->values();

        if ($users->isEmpty()) {
            return redirect("/group/{$groupId}/records?date={$date}");
        }

        $userIds = $users->pluck('id');

        $maxTate = Record::whereIn('user_id', $userIds)
            ->where('date', $date)
            ->where('practice_type', 'official')
            ->max('tate_no');

        $newTate = $maxTate ? $maxTate + 1 : 1;

        foreach ($users as $user) {
            $this->ensureRecordWithShots($user->id, $date, $newTate);
        }

        return redirect("/group/{$groupId}/records?date={$date}");
    }

    public function updateShot(Request $request, $id)
    {
        $shot = Shot::with('record')->findOrFail($id);

        $record = $shot->record;

        $groupId = Lineup::where('date', $record->date)
            ->whereHas('members', function ($q) use ($record) {
                $q->where('user_id', $record->user_id);
            })
            ->value('group_id');

        if ($groupId) {
            $this->checkGroupAccess($groupId);
        }

        $shot->result = $request->result ?: null;
        $shot->save();

        return response()->json(['success' => true]);
    }

    private function checkGroupAccess($groupId): void
    {
        $user = auth()->user();

        if (!$user || !$user->groups()->where('groups.id', $groupId)->exists()) {
            abort(403, 'このグループにはアクセスできません');
        }
    }

    private function syncLineupMembers(Lineup $lineup, Group $group): void
    {
        $existingUserIds = $lineup->members->pluck('user_id')->toArray();

        foreach ($group->users as $user) {
            if (!in_array($user->id, $existingUserIds)) {
                LineupMember::create([
                    'lineup_id' => $lineup->id,
                    'user_id' => $user->id,
                    'position' => null,
                    'is_absent' => false
                ]);
            }
        }
    }

    private function ensureRecordWithShots($userId, $date, $tateNo): Record
    {
        $record = Record::firstOrCreate([
            'user_id' => $userId,
            'date' => $date,
            'tate_no' => $tateNo,
            'practice_type' => 'official'
        ]);

        for ($i = 1; $i <= 4; $i++) {
            Shot::firstOrCreate([
                'record_id' => $record->id,
                'shot_no' => $i
            ], [
                'result' => null
            ]);
        }

        return $record;
    }
}