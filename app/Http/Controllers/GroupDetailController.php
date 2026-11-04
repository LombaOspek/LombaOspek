<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel anggota satu gugus.
 *
 * Ini yang menghilangkan ketergantungan pada import CSV: panitia bisa
 * menambah mahasiswa susulan, mengeluarkan yang salah gugus, dan melihat
 * kapasitas - tanpa menyentuh berkas.
 */
class GroupDetailController extends Controller
{
    public function show(Request $request, Group $group): View
    {
        abort_unless($request->user()->role->canManageAccounts(), 403);

        $members = User::query()
            ->where('group_id', $group->id)
            ->orderBy('name')
            ->get(['id', 'nim', 'name', 'email', 'major', 'xp', 'coins']);

        $officers = User::query()
            ->where('role', \App\Enums\UserRole::LiaisonOfficer)
            ->where('major', $group->study_program)
            ->get(['id', 'name', 'nim']);

        return view('pages.admin.group-detail', [
            'group' => $group,
            'members' => $members,
            'officers' => $officers,
            'capacity' => $group->capacity ?? 0,
            'available' => max(0, ($group->capacity ?? 0) - $members->count()),
        ]);
    }
}
