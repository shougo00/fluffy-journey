<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Record;
use App\Models\Shot;

class GroupRecordController extends Controller
{
    public function index(Request $request, $groupId)
    {
        $group = Group::with('users')->findOrFail($groupId);
        $date = $request->date ?? date('Y-m-d');

        $userIds = $group->users->pluck('id');

        $records = Record::with('shots')
            ->whereIn('user_id', $userIds)
            ->where('date', $date)
            ->where('practice_type', 'official')
            ->get()
            ->groupBy('user_id');

        // ★ 昇順にする（これ重要）
        $tates = Record::whereIn('user_id', $userIds)
            ->where('date', $date)
            ->where('practice_type', 'official')
            ->pluck('tate_no')
            ->unique()
            ->sort() // ←ここ
            ->values();

        return view('group.records', compact('group','records','tates','date'));
    }

    public function addTate($groupId)
    {
        $group = Group::with('users')->findOrFail($groupId);
        $date = date('Y-m-d');

        foreach ($group->users as $user) {

            $maxTate = Record::where('user_id', $user->id)
                ->where('date', $date)
                ->where('practice_type', 'official')
                ->max('tate_no');

            $newTate = $maxTate ? $maxTate + 1 : 1;

            $record = Record::create([
                'user_id' => $user->id,
                'date' => $date,
                'tate_no' => $newTate,
                'practice_type' => 'official'
            ]);

            for ($i=1; $i<=4; $i++) {
                Shot::create([
                    'record_id'=>$record->id,
                    'shot_no'=>$i,
                    'result'=>null
                ]);
            }
        }

        return back();
    }

    public function updateShot(Request $request, $id)
    {
        $shot = Shot::findOrFail($id);
        $shot->result = $request->result;
        $shot->save();

        return response()->json(['success'=>true]);
    }
}