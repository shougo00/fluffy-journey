<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LineWebhookController extends Controller
{
    public function handle(Request $request)
    {
        foreach ($request->input('events', []) as $event) {
            if (($event['type'] ?? null) !== 'message') continue;
            if (($event['message']['type'] ?? null) !== 'text') continue;

            $text = trim($event['message']['text']);
            $lineUserId = $event['source']['userId'] ?? null;

            if (!$lineUserId) continue;

            Log::info('LINE message', [
                'text' => $text,
                'line_user_id' => $lineUserId,
            ]);

            // 連携 123456
            if (preg_match('/^連携\s*([0-9]{6})$/u', $text, $matches)) {
                $code = $matches[1];

                $user = User::where('line_link_code', $code)->first();

                if (!$user) {
                    Log::info('LINE連携コードが見つかりません', [
                        'code' => $code,
                    ]);
                    return response('OK', 200);
                }

                $user->line_user_id = $lineUserId;
                $user->line_link_code = null;
                $user->save();

                Log::info('LINE連携完了', [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'line_user_id' => $lineUserId,
                ]);

                return response('OK', 200);
            }

            // 連携済みユーザー確認
            $user = User::where('line_user_id', $lineUserId)->first();

            if (!$user) {
                Log::info('LINE未連携ユーザーです');
                return response('OK', 200);
            }

            if (str_contains($text, '休み') || str_contains($text, '欠席')) {
                Log::info('欠席登録予定', [
                    'user_id' => $user->id,
                    'name' => $user->name,
                ]);
            }

            if (str_contains($text, '出席') || str_contains($text, '行きます')) {
                Log::info('出席登録予定', [
                    'user_id' => $user->id,
                    'name' => $user->name,
                ]);
            }
        }

        return response('OK', 200);
    }
}