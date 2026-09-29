<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['creator', 'targetUser'])->latest()->paginate(15);
        $collectors = User::where('role', 'acopiador')->get();
        $producers = User::where('role', 'productor')->get();

        return view('anuncios.index', compact('announcements', 'collectors', 'producers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'target_role' => ['nullable', 'string'],
            'target_user_id' => ['nullable', 'exists:users,id'],
        ]);

        app(\App\Services\Sistema\SistemaService::class)->publicarAviso(Auth::user(), [
            'title' => $validated['title'],
            'message' => $validated['message'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'target_role' => $validated['target_role'] ?? null,
            'target_user_id' => $validated['target_user_id'] ?? null,
        ]);

        return back()->with('success', 'Anuncio publicado. Aparecerá en el inicio de sesión de los destinatarios dentro del rango de fechas.');
    }
}
