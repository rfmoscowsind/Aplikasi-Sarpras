<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JuaraStudentDirectory
{
    public function search(string $query, int $limit = 10): Collection
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return collect();
        }

        $table = (string) config('services.juara.student_directory', 'sarpras_student_directory');

        return DB::connection('juara')
            ->table($table)
            ->select(['student_id', 'name', 'nis', 'class_name', 'class_code'])
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', $query.'%')
                    ->orWhere('nis', 'like', $query.'%');
            })
            ->orderBy('name')
            ->limit(min(max($limit, 1), 10))
            ->get();
    }

    public function find(int $studentId): ?object
    {
        $table = (string) config('services.juara.student_directory', 'sarpras_student_directory');

        return DB::connection('juara')
            ->table($table)
            ->select(['student_id', 'name', 'nis', 'class_name', 'class_code'])
            ->where('student_id', $studentId)
            ->first();
    }
}
