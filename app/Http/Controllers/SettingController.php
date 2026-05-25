<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $group = $this->currentGroup($request);
        $user = $request->user();
        $unlocked = $group && $request->session()->get($this->sessionKey($group->id), false);

        return view('settings.index', compact('group', 'user', 'unlocked'));
    }

    public function unlock(Request $request): RedirectResponse
    {
        $group = $this->currentGroup($request);

        if (!$group) {
            return back()->withErrors(['password' => 'グループに参加していません。']);
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if ($request->password !== 'kyudo') {
            return back()->withErrors(['password' => '管理者パスワードが正しくありません。']);
        }

        $request->session()->put($this->sessionKey($group->id), true);

        return redirect()->route('settings.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $group = $this->currentGroup($request);

        if (!$group) {
            return back()->withErrors(['official_tates_per_page' => 'グループに参加していません。']);
        }

        if (!$request->session()->get($this->sessionKey($group->id), false)) {
            return redirect()->route('settings.index');
        }

        $validated = $request->validate([
            'official_tates_per_page' => ['required', 'integer', 'min:1', 'max:10'],
            'official_record_height_extra' => ['required', 'integer', 'in:0,30,60,90,120'],
        ]);

        $group->update([
            'official_tates_per_page' => $validated['official_tates_per_page'],
        ]);

        $request->user()->update([
            'official_record_height_extra' => $validated['official_record_height_extra'],
        ]);

        return back()->with('status', 'settings-updated');
    }

    private function currentGroup(Request $request): ?Group
    {
        return $request->user()
            ? $request->user()->groups()->with('host')->first()
            : null;
    }

    private function sessionKey(int $groupId): string
    {
        return "settings_unlocked_group_{$groupId}";
    }
}
