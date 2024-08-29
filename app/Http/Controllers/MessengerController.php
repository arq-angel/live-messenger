<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessengerController extends Controller
{
    public function index()
    {
        return view('messenger.index');
    }

    /** Search User Profiles */
    public function search(Request $request)
    {
        $getRecords = null;
        $query = $request['query'];
        $records = User::where('id', '!=', Auth::user()->id)
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('user_name', 'LIKE', "%{$query}%")
            ->paginate(10);

        if($records->total() < 1) {
            $getRecords .= "<p class='text-center'>Nothing to show!</p>";
        }

        foreach ($records as $record) {
            $getRecords .= view('messenger.components.search-item', compact('record'))->render();
        }

        return response()->json([
            'records' => $getRecords,
            'last_page' => $records->lastPage(),
        ]);
    }
}
