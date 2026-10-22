<?php

namespace Database\Seeders;

use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use App\Models\Block;
use App\Models\Hall;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect([
            'super_admin',
            'provost',
            'hall_office_staff',
            'house_tutor',
            'club_admin',
            'student',
        ])->mapWithKeys(fn (string $name) => [
            $name => Role::query()->firstOrCreate(['name' => $name]),
        ]);

        $accounts = [
            ['name' => 'Super Admin', 'email' => 'admin@kjh.test', 'roles' => ['super_admin']],
            ['name' => 'Provost', 'email' => 'provost@kjh.test', 'roles' => ['provost']],
            ['name' => 'Hall Office Staff', 'email' => 'office@kjh.test', 'roles' => ['hall_office_staff']],
            ['name' => 'North Block House Tutor', 'email' => 'tutor@kjh.test', 'roles' => ['house_tutor']],
            ['name' => 'Club Admin', 'email' => 'clubadmin@student.du.test', 'roles' => ['club_admin', 'student']],
            ['name' => 'Resident Student', 'email' => 'student@student.du.test', 'roles' => ['student']],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            $user->roles()->sync(
                $roles->only($account['roles'])->pluck('id')->all(),
            );
        }

        $hall = Hall::query()->firstOrCreate(['name' => 'Kazi Nazrul Islam Hall']);
        $block = Block::query()->firstOrCreate(
            ['hall_id' => $hall->id, 'code' => 'NORTH'],
            ['name' => 'North Block'],
        );

        $tutor = User::query()->where('email', 'tutor@kjh.test')->firstOrFail();
        $tutorProfile = $tutor->tutorProfile()->updateOrCreate([], [
            'designation' => 'Assistant Professor',
            'department' => 'Physics',
            'phone' => '01712345678',
            'is_active' => true,
        ]);
        $tutorProfile->blocks()->syncWithoutDetaching([$block->id]);

        User::query()->where('email', 'clubadmin@student.du.test')->firstOrFail()
            ->studentProfile()
            ->updateOrCreate([], [
                'registration_number' => '2023000101',
                'department' => 'English',
                'session' => '2023-24',
                'phone' => '01712345678',
                'registration_status' => RegistrationStatus::Approved,
                'residency_status' => ResidencyStatus::Attached,
            ]);

        User::query()->where('email', 'student@student.du.test')->firstOrFail()
            ->studentProfile()
            ->updateOrCreate([], [
                'registration_number' => '2023000102',
                'department' => 'History',
                'session' => '2023-24',
                'phone' => '01812345678',
                'registration_status' => RegistrationStatus::Approved,
                'residency_status' => ResidencyStatus::Resident,
            ]);
    }
}
