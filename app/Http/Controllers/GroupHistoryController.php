<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Record;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GroupHistoryController extends Controller
{
    public function index(Request $request, Group $group)
    {
        $period = $request->input('period', 'today');
        $limit = $request->input('limit', 10);

        [$start, $end] = $this->periodRange($period);

        $members = $group->users()->with('avatar')->get();

        $ranking = $members->map(function ($user) use ($start, $end) {
            $records = Record::with('shots')
                ->where('user_id', $user->id)
                ->whereBetween('date', [$start, $end])
                ->get();

            $all = $this->calc($records);
            $official = $this->calc($records->where('practice_type', 'official'));
            $self = $this->calc($records->where('practice_type', 'self'));

            return [
                'user' => $user,
                'all' => $all,
                'official' => $official,
                'self' => $self,
            ];
        })
        ->sortByDesc(function ($row) {
            return $row['all']['hits'];
        })
        ->values();

        if ($limit !== 'all') {
            $ranking = $ranking->take((int) $limit);
        }

        // 性別カラムが male / female 想定
        $maleRanking = $ranking->filter(function ($row) {
            return $row['user']->gender === 'male';
        })->values();

        $femaleRanking = $ranking->filter(function ($row) {
            return $row['user']->gender === 'female';
        })->values();

        return view('group_history.index', compact(
            'group',
            'period',
            'limit',
            'maleRanking',
            'femaleRanking'
        ));
    }

    private function calc($records)
    {
        $shots = $records->sum(function ($record) {
            return $record->shots->whereNotNull('result')->count();
        });

        $hits = $records->sum(function ($record) {
            return $record->shots->where('result', 'hit')->count();
        });

        $rate = $shots > 0 ? round(($hits / $shots) * 100, 1) : 0;

        return [
            'shots' => $shots,
            'hits' => $hits,
            'rate' => $rate,
        ];
    }

    private function periodRange($period)
    {
        if ($period === 'week') {
            return [
                now()->subDays(6)->format('Y-m-d'),
                now()->format('Y-m-d'),
            ];
        }

        if ($period === 'month') {
            return [
                now()->subDays(29)->format('Y-m-d'),
                now()->format('Y-m-d'),
            ];
        }

        if ($period === 'year') {
            return [
                now()->startOfYear()->format('Y-m-d'),
                now()->endOfYear()->format('Y-m-d'),
            ];
        }

        return [
            now()->format('Y-m-d'),
            now()->format('Y-m-d'),
        ];
    }
}