<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Lineup;
use App\Models\LineupMember;

class LineupController extends Controller
{
    // ===== 画面表示 =====
    public function index(Request $request, $groupId)
    {
        $group = Group::with('users')->findOrFail($groupId);
        $date = $request->date ?? date('Y-m-d');

        // ===== 立順取得 or 作成 =====
        $lineup = Lineup::firstOrCreate(
            [
                'group_id' => $groupId,
                'date' => $date
            ],
            [
                'tate_size' => 3
            ]
        );

        // ===== 既存メンバー取得 =====
        $members = $lineup->members()->with('user')->get();

        // ===== 既に登録済みユーザーID =====
        $existingUserIds = $members->pluck('user_id')->toArray();

        // ===== 不足メンバーを追加（これが重要） =====
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

        // ===== 再取得（重要） =====
        $members = $lineup->members()->with('user')->get();

        return view('lineup.index', compact('group','lineup','members','date'));
    }

    // ===== 保存 =====
    public function save(Request $request, $lineupId)
    {
        foreach($request->members as $m){
            LineupMember::where('id', $m['id'])->update([
                'position' => $m['position'],
                'is_absent' => $m['absent']
            ]);
        }

        Lineup::where('id',$lineupId)->update([
            'tate_size' => $request->tate_size
        ]);

        return response()->json(['ok'=>true]);
    }

    // ===== ランダム =====
    public function random($lineupId)
    {
        $members = LineupMember::where('lineup_id',$lineupId)
            ->where('is_absent',false)
            ->get()
            ->shuffle()
            ->values();

        foreach($members as $i=>$m){
            $m->update([
                'position' => $i+1
            ]);
        }

        return response()->json(['ok'=>true]);
    }
}