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
        if (!$group->users()->where('users.id', auth()->id())->exists()) {
            abort(403);
        }

        $view = $request->input('view', 'ranking');

        $scoreType = $request->input('score_type', 'all');
        $period = $request->input('period', 'today');
        $limit = $request->input('limit', 10);

        // ===== 月間記録用 =====
        $month = $request->input('month', now()->format('Y-m'));
        $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // ===== グループメンバー =====
        $members = $group->users()
            ->where('is_admin', false)
            ->with('avatar')
            ->get();

        // ===== ランキング用 =====
        [$start, $end] = $this->periodRange($period);

        $memberIds = $members->pluck('id');

        $rankingSourceRecords = Record::with('shots')
            ->whereIn('user_id', $memberIds)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('user_id');

        $ranking = $members->map(function ($user) use ($rankingSourceRecords) {
            $records = $rankingSourceRecords->get($user->id, collect());

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
            $ranking->filter(fn($row) => $row['user']->gender === 'male')
        );

        $femaleRanking = $sortRanking(
            $ranking->filter(fn($row) => $row['user']->gender === 'female')
        );

        // ===== 月間記録用 =====
        // records に group_id が無いので、メンバーの user_id で絞る
       // ===== 月間記録用 =====
        $monthlyMembers = $group->users()
            ->where('is_admin', false)
            ->with('avatar')
            ->get();

        $memberIds = $monthlyMembers->pluck('id');

        $monthlySourceRecords = Record::with('shots')
            ->whereIn('user_id', $memberIds)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get();

        $monthlyRecords = $monthlyMembers
            ->sortBy('name')
            ->map(function ($user) use ($monthlySourceRecords) {
                $records = $monthlySourceRecords->where('user_id', $user->id);

                return [
                    'user' => $user,
                    'all' => $this->calc($records),
                    'official' => $this->calc($records->where('practice_type', 'official')),
                    'self' => $this->calc($records->where('practice_type', 'self')),
                ];
            })
            ->values();

        return view('group_history.index', compact(
            'group',
            'view',
            'period',
            'limit',
            'scoreType',
            'maleRanking',
            'femaleRanking',
            'month',
            'currentMonth',
            'prevMonth',
            'nextMonth',
            'monthlyRecords'
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
    public function monthlyPrint(Request $request, Group $group)
    {
        // ★ 所属チェック
        if (!$group->users()->where('users.id', auth()->id())->exists()) {
            abort(403);
        }

        $month = $request->month ?? now()->format('Y-m');
        $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $members = $group->users()
            ->where('is_admin', false)
            ->get()
            ->sortBy('name');

        $memberIds = $members->pluck('id');

        $records = Record::with('shots')
            ->whereIn('user_id', $memberIds)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get();

        $rows = $members->map(function ($user) use ($records) {

            $userRecords = $records->where('user_id', $user->id);

            return [
                'name' => $user->name,
                'official' => $this->calc($userRecords->where('practice_type', 'official')),
                'self' => $this->calc($userRecords->where('practice_type', 'self')),
                'all' => $this->calc($userRecords),
            ];
        });

        return view('group_history.monthly_print', compact(
            'group',
            'currentMonth',
            'rows'
        ));
    }
}