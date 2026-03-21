<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Record;
use App\Models\Shot;
use Carbon\Carbon;

class RecordController extends Controller
{
    // 一覧表示
    
    public function index(Request $request)
    {
        $date = $request->date ?? date('Y-m-d');
        $type = $request->type ?? 'official'; // デフォルトは正規練

        $records = Record::with('shots')
            ->where('user_id', auth()->id())
            ->where('date', $date)
            ->where('practice_type', $type) // ←練習タイプで絞り込み
            ->orderBy('tate_no')
            ->get();

        $prevDate = Carbon::parse($date)->subDay()->format('Y-m-d');
        $nextDate = Carbon::parse($date)->addDay()->format('Y-m-d');

        return view('home', compact('records', 'date', 'prevDate', 'nextDate', 'type'));
    }

    // 立追加
    public function store(Request $request)
    {
        $date = $request->date;
        $practiceType = $request->practice_type; // 正規練 / 自主練

        // その日のその練習タイプの最大立番号を取得
        $maxTate = Record::where('user_id', auth()->id())
            ->where('date', $date)
            ->where('practice_type', $practiceType) // ←ここ重要
            ->max('tate_no');

        $newTate = $maxTate ? $maxTate + 1 : 1; // 練習タイプごとに1立目から

        $record = Record::create([
            'user_id' => auth()->id(),
            'date' => $date,
            'tate_no' => $newTate,
            'practice_type' => $practiceType
        ]);

        // 4射作成
        for ($i = 1; $i <= 4; $i++) {
            Shot::create([
                'record_id' => $record->id,
                'shot_no' => $i,
                'result' => null
            ]);
        }

        return redirect()->back();
    }
    public function updateShot(Request $request, $id)
    {
        $shot = Shot::findOrFail($id);

        $shot->result = $request->result;
        $shot->save();

        return response()->json(['success' => true]);
    }
    // 立削除
    public function destroy($id)
    {
        $record = Record::where('user_id', auth()->id())->findOrFail($id);

        $date = $record->date;
        $practiceType = $record->practice_type;

        // 射を削除
        $record->shots()->delete();

        // 立を削除
        $record->delete();

        // 残った立番号を詰める
        $remainingRecords = Record::where('user_id', auth()->id())
            ->where('date', $date)
            ->where('practice_type', $practiceType)
            ->orderBy('tate_no')
            ->get();

        $tateNo = 1;
        foreach($remainingRecords as $r) {
            $r->tate_no = $tateNo++;
            $r->save();
        }

        return response()->json(['success' => true]);
    }
}