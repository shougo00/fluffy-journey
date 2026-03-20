<!-- resources/views/group_select.blade.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>グループ選択</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded shadow-md w-96 text-center">
        <h1 class="text-2xl font-bold mb-6">グループを選択</h1>

        <div class="flex flex-col gap-4">
            <a href="#" class="bg-blue-500 text-white py-2 rounded hover:bg-blue-600">グループに参加する</a>
            <a href="#" class="bg-green-500 text-white py-2 rounded hover:bg-green-600">新しいグループを作成する</a>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="text-red-500 hover:underline">ログアウト</button>
        </form>
    </div>
</body>
</html>