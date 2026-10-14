<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Major;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_projects_are_excluded_from_public_lists_and_categories(): void
    {
        [$visible, $hidden] = $this->createProjects();

        foreach (['/api/projects', '/api/projects/featured', '/api/projects/top-liked', "/api/projects/{$visible->id}/related"] as $url) {
            $response = $this->getJson($url)->assertOk();
            $this->assertNotContains($hidden->id, array_column($response->json('data'), 'id'));
            if (!str_ends_with($url, '/related')) {
                $this->assertContains($visible->id, array_column($response->json('data'), 'id'));
            }
        }

        $this->getJson("/api/categories/{$visible->category_id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.projects')
            ->assertJsonPath('data.projects.0.id', $visible->id);

        $this->assertCount(2, Project::all());
        $hidden->update(['is_visible' => true]);
        $this->getJson('/api/projects')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_hidden_projects_cannot_be_viewed_or_liked(): void
    {
        [$visible, $hidden] = $this->createProjects();

        $this->getJson("/api/projects/{$visible->id}")->assertOk();
        $this->getJson("/api/projects/{$hidden->id}")->assertNotFound();
        $this->getJson("/api/projects/{$hidden->id}/related")->assertNotFound();
        $this->postJson("/api/projects/{$hidden->id}/like")->assertNotFound();
        $this->assertSame(100, $hidden->fresh()->likes_count);
    }

    private function createProjects(): array
    {
        $major = Major::create(['name' => ['en' => 'Major'], 'description' => ['en' => 'Description']]);
        $category = Category::create([
            'major_id' => $major->id,
            'name' => ['en' => 'Category'],
            'description' => ['en' => 'Description'],
        ]);
        $data = [
            'category_id' => $category->id,
            'name' => ['en' => 'Project'],
            'description' => ['en' => 'Description'],
            'project_number' => '1',
            'is_featured' => true,
        ];

        $visible = Project::create($data)->fresh();
        $this->assertTrue($visible->is_visible);
        $hidden = Project::create([...$data, 'project_number' => '2', 'is_visible' => false, 'likes_count' => 100]);

        return [$visible, $hidden];
    }
}
