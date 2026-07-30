<?php

namespace Database\Seeders;

use App\Enums\ChangeType;
use App\Enums\ProfileStatus;
use App\Enums\ReleaseType;
use App\Enums\RepositoryVisibility;
use App\Models\ChangeEntry;
use App\Models\Release;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $primaryUser = User::factory()->create([
            'name' => 'Mara Chen',
            'display_name' => 'Mara Chen',
            'handle' => 'mara-chen',
            'email' => 'mara@example.test',
            'bio' => 'Keeping practical notes on the systems that support everyday life.',
            'status' => ProfileStatus::ActiveDevelopment,
            'timezone' => 'America/New_York',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Jonah Rivera',
            'display_name' => 'Jonah Rivera',
            'handle' => 'jonah-rivera',
            'email' => 'jonah@example.test',
            'bio' => 'A fictional local account for checking private boundaries.',
            'status' => ProfileStatus::Stable,
            'timezone' => 'America/Chicago',
            'password' => Hash::make('password'),
        ]);

        $health = Repository::factory()->public()->for($primaryUser, 'owner')->create([
            'name' => 'Health routines',
            'normalized_name' => 'health routines',
            'slug' => 'health-routines',
            'description' => 'Small, sustainable adjustments to rest, movement, and care.',
            'status' => ProfileStatus::ActiveDevelopment,
        ]);
        $home = Repository::factory()->unlisted()->for($primaryUser, 'owner')->create([
            'name' => 'Home systems',
            'normalized_name' => 'home systems',
            'slug' => 'home-systems',
            'description' => 'Private-by-link notes on making home life easier to maintain.',
            'status' => ProfileStatus::MaintenanceMode,
        ]);
        $work = Repository::factory()->private()->for($primaryUser, 'owner')->create([
            'name' => 'Work transition',
            'normalized_name' => 'work transition',
            'slug' => 'work-transition',
            'description' => 'A private record of a changing professional season.',
            'status' => ProfileStatus::BreakingChangesExpected,
        ]);
        Repository::factory()->public()->archived()->for($primaryUser, 'owner')->create([
            'name' => 'Long-view notes',
            'normalized_name' => 'long-view notes',
            'slug' => 'long-view-notes',
            'description' => 'An archived record that remains available without inviting further edits.',
            'status' => ProfileStatus::LongTermSupport,
        ]);

        $draft = Release::factory()->draft()->for($work)->create([
            'version' => '0.1.0',
            'release_type' => ReleaseType::Experimental,
            'title' => 'Questions to return to',
            'body' => "A working note, not a finished conclusion.\nIt can stay incomplete.",
            'visibility' => RepositoryVisibility::Private,
        ]);
        ChangeEntry::factory()->for($draft)->create([
            'change_type' => ChangeType::KnownIssue,
            'content' => 'The next step is not clear yet, and that is worth recording.',
            'sort_order' => 0,
        ]);

        $releases = [
            [$health, '1.0.0', ReleaseType::Major, RepositoryVisibility::Public, 'A steadier baseline', ChangeType::Added, 'Added a gentler morning routine.'],
            [$health, '1.1.0', ReleaseType::Minor, RepositoryVisibility::Public, 'Movement that fits the week', ChangeType::Improved, 'Improved the plan by choosing shorter walks more often.'],
            [$health, '1.1.1', ReleaseType::Patch, RepositoryVisibility::Public, 'A small rest adjustment', ChangeType::Fixed, 'Fixed an unrealistic evening reminder.'],
            [$home, '0.2.0', ReleaseType::Hotfix, RepositoryVisibility::Unlisted, 'Repair the entryway friction', ChangeType::Removed, 'Removed an unnecessary task from the arrival routine.'],
            [$home, '0.2.1', ReleaseType::Experimental, RepositoryVisibility::Unlisted, 'Try a shared reset list', ChangeType::Deprecated, 'Deprecated the old all-or-nothing cleaning checklist.'],
            [$home, '0.2.2', ReleaseType::Rollback, RepositoryVisibility::Unlisted, 'Return to the simpler plan', ChangeType::KnownIssue, 'Recorded that the more complex approach still creates too much pressure.'],
        ];

        foreach ($releases as [$repository, $version, $releaseType, $visibility, $title, $changeType, $content]) {
            $release = Release::factory()->published()->for($repository)->create([
                'version' => $version,
                'release_type' => $releaseType,
                'title' => $title,
                'body' => "A fictional local journal entry.\nPlain text remains plain text.",
                'visibility' => $visibility,
            ]);

            ChangeEntry::factory()->for($release)->create([
                'change_type' => $changeType,
                'content' => $content,
                'sort_order' => 0,
            ]);
        }
    }
}
