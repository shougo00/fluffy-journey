<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Lineup;
use App\Models\LineupMember;

class AttendanceController extends Controller
{
   public function index(Request $request, $groupId)
{
    $user = auth()->user();

    // ★ここ追加（超重要）
    if (!$user->groups()->where('groups.id', $groupId)->exists()) {
        abort(403, 'このグループにはアクセスできません');
    }

    $group = Group::findOrFail($groupId);
    $date = $request->date ?? date('Y-m-d');

    $lineup = Lineup::firstOrCreate(
        [
            'group_id' => $groupId,
            'date' => $date,
        ],
        [
            'tate_size' => 3,
        ]
    );

    $member = LineupMember::firstOrCreate(
        [
            'lineup_id' => $lineup->id,
            'user_id' => $user->id,
        ],
        [
            'position' => null,
            'is_absent' => false,
        ]
    );

    return view('attendance.index', compact(
        'group',
        'user',
        'date',
        'lineup',
        'member'
    ));
}

    public function save(Request $request, $groupId)
    {
        $request->validate([
            'absent' => 'required|boolean',
            'date' => 'required|date',
        ]);

        $date = $request->date;
        $user = auth()->user();

        $lineup = Lineup::firstOrCreate(
            [
                'group_id' => $groupId,
                'date' => $date,
            ],
            [
                'tate_size' => 3,
            ]
        );

        $member = LineupMember::firstOrCreate(
            [
                'lineup_id' => $lineup->id,
                'user_id' => $user->id,
            ],
            [
                'position' => null,
                'is_absent' => false,
            ]
        );

        $member->update([
            'is_absent' => $request->absent,
        ]);

        return response()->json(['ok' => true]);
    }
}