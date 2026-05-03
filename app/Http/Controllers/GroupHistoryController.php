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
        $scoreType = $request->input('score_type', 'all');
        $period = $request->input('period', 'today');
        $limit = $request->input('limit', 10);

        [$start, $end] = $this->periodRange($period);

        $members = $group->users()->with('avatar')->get();

        $ranking = $members->map(function ($user) use ($start, $end) {
            $records = Record::with('shots')
                ->where('user_id', $user->id)
                ->whereBetween('date', [$start, $end])
                ->get();

            return [
                'user' => $user,
                'all' => $this->calc($records),
                'official' => $this->calc($records->where('practice_type', 'official')),
                'self' => $this->calc($records->where('practice_type', 'self')),
            ];
        });

        $sortRanking = function ($items) use ($limit, $scoreType) {
            $items = $items
                ->sort(function ($a, $b) use ($scoreType) {
                    if ($a[$scoreType]['rate'] == $b[$scoreType]['rate']) {
                        return $b[$scoreType]['hits'] <=> $a[$scoreType]['hits'];
                    }

                    return $b[$scoreType]['rate'] <=> $a[$scoreType]['rate'];
                })
                ->values();

            if ($limit !== 'all') {
                $items = $items->take((int) $limit)->values();
            }

            return $items;
        };

        $maleRanking = $sortRanking(
            $ranking->filter(function ($row) {
                return $row['user']->gender === 'male';
            })
        );

        $femaleRanking = $sortRanking(
            $ranking->filter(function ($row) {
                return $row['user']->gender === 'female';
            })
        );

        return view('group_history.index', compact(
            'group',
            'period',
            'limit',
            'scoreType',
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