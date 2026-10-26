<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminLectureManagementSortingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Display names deliberately break alphabetical order ("Mastery C2" < "Beginner A1"),
     * which proves we rank by the level code, not the displayed name.
     */
    private function seedAcrossLevels(): void
    {
        $levels = [
            'A1' => 'Beginner A1', 'A2' => 'Elementary A2', 'B1' => 'Intermediate B1',
            'B2' => 'Upper-Intermediate B2', 'C1' => 'Advanced C1', 'C2' => 'Mastery C2',
        ];
        $i = 0;
        foreach ($levels as $code => $display) {
            $i++;
            DB::table('levels')->insert([
                'id' => $i, 'name' => $display, 'level' => $code, 'tier' => $code,
                'description' => $code, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->lesson($i, $code . ' Second Lesson', 2);
            $this->lesson($i, $code . ' First Lesson', 1);
        }
    }

    private function lesson(int $levelId, string $title, int $sortOrder): Lesson
    {
        $now = now();
        $categoryId = DB::table('categories')->insertGetId([
            'level_id' => $levelId, 'title' => 'Category ' . $levelId, 'sort_order' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return Lesson::create([
            'level_id' => $levelId, 'category_id' => $categoryId, 'title' => $title,
            'type' => 'training', 'score' => 0, 'sort_order' => $sortOrder,
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    /** Walk every page and return the level code of each rendered row in order. */
    private function allLevelCodes(array $query): array
    {
        $codes = [];
        $page = 1;
        do {
            $response = $this->actingAs($this->superAdmin())
                ->get(route('super.admin.lessons.index', $query + ['page' => $page]));
            $response->assertOk();
            $lessons = $response->viewData('lessons');
            foreach ($lessons->items() as $lesson) {
                $codes[] = $lesson->level->level;
            }
            $page++;
        } while ($lessons->hasMorePages());

        return $codes;
    }

    public function test_ascending_level_order_is_a1_through_c2(): void
    {
        $this->seedAcrossLevels();
        $this->assertSame(
            ['A1', 'A1', 'A2', 'A2', 'B1', 'B1', 'B2', 'B2', 'C1', 'C1', 'C2', 'C2'],
            $this->allLevelCodes(['sort' => 'asc'])
        );
    }

    public function test_descending_level_order_is_c2_through_a1(): void
    {
        $this->seedAcrossLevels();
        $this->assertSame(
            ['C2', 'C2', 'C1', 'C1', 'B2', 'B2', 'B1', 'B1', 'A2', 'A2', 'A1', 'A1'],
            $this->allLevelCodes(['sort' => 'desc'])
        );
    }

    public function test_first_round_titles_stay_in_sort_order_then_title(): void
    {
        $this->seedAcrossLevels();
        $firstPage = $this->actingAs($this->superAdmin())
            ->get(route('super.admin.lessons.index', ['sort' => 'asc', 'page' => 1]))
            ->viewData('lessons');

        $titles = collect($firstPage->items())->pluck('title')->all();
        // Within each level, "First Lesson" (sort 1) precedes "Second Lesson" (sort 2).
        $this->assertSame('A1 First Lesson', $titles[0]);
        $this->assertSame('A1 Second Lesson', $titles[1]);
        $this->assertSame('A2 First Lesson', $titles[2]);
        $this->assertSame('A2 Second Lesson', $titles[3]);
    }

    public function test_search_returns_partial_matches_case_insensitively(): void
    {
        $this->seedAcrossLevels();
        $response = $this->actingAs($this->superAdmin())
            ->get(route('super.admin.lessons.index', ['q' => 'first']))
            ->assertOk();

        $this->assertSame(6, $response->viewData('lessons')->total());
        $response->assertSee('value="first"', false);
    }

    public function test_search_with_no_match_returns_empty_state(): void
    {
        $this->seedAcrossLevels();
        $this->actingAs($this->superAdmin())
            ->get(route('super.admin.lessons.index', ['q' => 'zzz-no-such-title']))
            ->assertOk()
            ->assertSee('No lessons match your search for "zzz-no-such-title".', false);
    }

    public function test_search_and_sort_together_and_pagination_preserves_query(): void
    {
        $this->seedAcrossLevels();
        $response = $this->actingAs($this->superAdmin())
            ->get(route('super.admin.lessons.index', ['q' => 'lesson', 'sort' => 'desc']))
            ->assertOk();

        $this->assertSame(12, $response->viewData('lessons')->total());

        $html = $response->viewData('lessons')->links()->toHtml();
        $this->assertStringContainsString('q=lesson', $html);
        $this->assertStringContainsString('sort=desc', $html);
    }

    public function test_invalid_sort_value_falls_back_to_default_ordering(): void
    {
        $this->seedAcrossLevels();
        $response = $this->actingAs($this->superAdmin())
            ->get(route('super.admin.lessons.index', ['sort' => 'drop table; --']))
            ->assertOk();

        $titles = collect($response->viewData('lessons')->items())->pluck('title');
        $this->assertSame('A1 First Lesson', $titles->first());
        $this->assertSame('A2 First Lesson', $titles->skip(1)->first());
    }
}
