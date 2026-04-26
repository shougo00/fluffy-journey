<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Lineup;
use App\Models\LineupMember;

class LineupController extends Controller
{
    public function index(Request $request, $groupId)
    {
        $this->checkGroupAccess($groupId);

        $group = Group::with('users')->findOrFail($groupId);
        $date = $request->date ?? date('Y-m-d');

        $lineup = Lineup::firstOrCreate(
            [
                'group_id' => $groupId,
                'date' => $date
            ],
            [
                'tate_size' => 3
            ]
        );

        $this->syncLineupMembers($lineup, $group);

        $members = $lineup->members()->with('user')->get();

        return view('lineup.index', compact('group', 'lineup', 'members', 'date'));
    }

    public function save(Request $request, $lineupId)
    {
        $lineup = Lineup::findOrFail($lineupId);

        $this->checkGroupAccess($lineup->group_id);

        foreach ($request->members as $m) {
            LineupMember::where('id', $m['id'])
                ->where('lineup_id', $lineup->id)
                ->update([
                    'position' => $m['position'],
                    'is_absent' => $m['absent']
                ]);
        }

        $lineup->update([
            'tate_size' => $request->tate_size
        ]);

        return response()->json(['ok' => true]);
    }

    public function random($lineupId)
    {
        $lineup = Lineup::findOrFail($lineupId);

        $this->checkGroupAccess($lineup->group_id);

        $members = LineupMember::where('lineup_id', $lineupId)
            ->where('is_absent', false)
            ->get()
            ->shuffle()
            ->values();

        foreach ($members as $i => $m) {
            $m->update([
                'position' => $i + 1
            ]);
        }

        return response()->json(['ok' => true]);
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
        $members = $lineup->members()->get();
        $existingUserIds = $members->pluck('user_id')->toArray();

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
}