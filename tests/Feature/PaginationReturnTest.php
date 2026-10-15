<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaginationReturnTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::create([
            'name'     => 'Admin',
            'email'    => 'admin_test_' . uniqid() . '@example.com',
            'password' => bcrypt('secret123'),
        ]);
    }

    /**
     * Category Edit returns to page 2 when return_url is provided.
     */
    public function test_category_edit_returns_to_second_page()
    {
        $category = Category::create([
            'name'       => 'Test Category ' . uniqid(),
            'is_active'  => true,
            'sort_order' => 1,
            'folder'     => 'test_' . uniqid(),
        ]);

        $page2Url = route('categories.index', ['page' => 2]);

        // AJAX update (modal)
        $ajaxResponse = $this->actingAs($this->user)
            ->putJson(route('categories.update', $category), [
                'name'       => 'Updated Category Name',
                'return_url' => $page2Url,
            ]);

        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson(['redirect' => $page2Url]);

        // Standard HTTP update
        $response = $this->actingAs($this->user)
            ->put(route('categories.update', $category), [
                'name'       => 'Updated Category Name 2',
                'return_url' => $page2Url,
            ]);

        $response->assertRedirect($page2Url);
    }

    /**
     * Category Delete returns to page 2 when return_url is provided.
     */
    public function test_category_delete_returns_to_second_page()
    {
        $category = Category::create([
            'name'       => 'Delete Category ' . uniqid(),
            'is_active'  => true,
            'sort_order' => 1,
            'folder'     => 'del_' . uniqid(),
        ]);

        $page2Url = route('categories.index', ['page' => 2]);

        $response = $this->actingAs($this->user)
            ->delete(route('categories.destroy', $category), [
                'return_url' => $page2Url,
            ]);

        $response->assertRedirect($page2Url);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /**
     * Category index redirects to previous page if page > 1 is empty.
     */
    public function test_category_index_redirects_when_page_exceeds_total()
    {
        $response = $this->actingAs($this->user)
            ->get(route('categories.index', ['page' => 9999]));

        $response->assertRedirect();
        // Should redirect to a valid page (without page=9999)
        $this->assertStringNotContainsString('page=9999', $response->headers->get('Location'));
    }

    /**
     * SubCategory Edit returns to page 2 when return_url is provided.
     */
    public function test_subcategory_edit_returns_to_second_page()
    {
        $category = Category::create([
            'name'       => 'Parent Cat ' . uniqid(),
            'is_active'  => true,
            'sort_order' => 1,
            'folder'     => 'parent_' . uniqid(),
        ]);

        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name'        => 'Test SubCat ' . uniqid(),
            'is_active'   => true,
            'sort_order'  => 1,
        ]);

        $page2Url = route('sub-categories.index', ['page' => 2]);

        // AJAX update
        $ajaxResponse = $this->actingAs($this->user)
            ->putJson(route('sub-categories.update', $subCategory), [
                'category_id' => $category->id,
                'name'        => 'Updated SubCat Name',
                'return_url'  => $page2Url,
            ]);

        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson(['redirect' => $page2Url]);

        // Standard update
        $response = $this->actingAs($this->user)
            ->put(route('sub-categories.update', $subCategory), [
                'category_id' => $category->id,
                'name'        => 'Updated SubCat Name 2',
                'return_url'  => $page2Url,
            ]);

        $response->assertRedirect($page2Url);
    }

    /**
     * SubCategory Delete returns to page 2 when return_url is provided.
     */
    public function test_subcategory_delete_returns_to_second_page()
    {
        $category = Category::create([
            'name'       => 'Parent Cat ' . uniqid(),
            'is_active'  => true,
            'sort_order' => 1,
            'folder'     => 'parent_del_' . uniqid(),
        ]);

        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name'        => 'SubCat to delete ' . uniqid(),
            'is_active'   => true,
            'sort_order'  => 1,
        ]);

        $page2Url = route('sub-categories.index', ['page' => 2]);

        $response = $this->actingAs($this->user)
            ->delete(route('sub-categories.destroy', $subCategory), [
                'return_url' => $page2Url,
            ]);

        $response->assertRedirect($page2Url);
        $this->assertDatabaseMissing('sub_categories', ['id' => $subCategory->id]);
    }

    /**
     * Video Edit screen retains return_url and Video update redirects to page 2.
     */
    public function test_video_edit_and_update_returns_to_second_page()
    {
        $category = Category::create([
            'name'       => 'Video Cat ' . uniqid(),
            'is_active'  => true,
            'sort_order' => 1,
            'folder'     => 'vcat_' . uniqid(),
        ]);

        $video = Video::create([
            'category_id' => $category->id,
            'title'       => 'Test Video ' . uniqid(),
            'video_file'  => 'test.mp4',
            'sort_order'  => 1,
        ]);

        $page2Url = route('videos.index', ['page' => 2]);

        // GET edit screen with return_url
        $editScreen = $this->actingAs($this->user)
            ->get(route('videos.edit', ['video' => $video, 'return_url' => $page2Url]));

        $editScreen->assertStatus(200);
        $editScreen->assertSee(e($page2Url));

        // AJAX update video
        $ajaxResponse = $this->actingAs($this->user)
            ->putJson(route('videos.update', $video), [
                'category_id' => $category->id,
                'title'       => 'Updated Video Title',
                'return_url'  => $page2Url,
            ]);

        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson(['redirect' => $page2Url]);

        // Standard update video
        $response = $this->actingAs($this->user)
            ->put(route('videos.update', $video), [
                'category_id' => $category->id,
                'title'       => 'Updated Video Title 2',
                'return_url'  => $page2Url,
            ]);

        $response->assertRedirect($page2Url);
    }

    /**
     * Video Delete returns to page 2 when return_url or page is provided.
     */
    public function test_video_delete_returns_to_second_page()
    {
        $category = Category::create([
            'name'       => 'Video Cat Del ' . uniqid(),
            'is_active'  => true,
            'sort_order' => 1,
            'folder'     => 'vcat_del_' . uniqid(),
        ]);

        $video = Video::create([
            'category_id' => $category->id,
            'title'       => 'Video to delete ' . uniqid(),
            'video_file'  => 'test.mp4',
            'sort_order'  => 1,
        ]);

        $page2Url = route('videos.index', ['page' => 2, 'category_id' => $category->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('videos.destroy', $video), [
                'return_url' => $page2Url,
            ]);

        $response->assertRedirect($page2Url);
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    /**
     * SubCategory index redirects to last page if page exceeds total.
     */
    public function test_subcategory_index_redirects_when_page_exceeds_total()
    {
        $response = $this->actingAs($this->user)
            ->get(route('sub-categories.index', ['page' => 9999]));

        $response->assertRedirect();
        $this->assertStringNotContainsString('page=9999', $response->headers->get('Location'));
    }

    /**
     * Video index redirects to last page if page exceeds total.
     */
    public function test_video_index_redirects_when_page_exceeds_total()
    {
        $response = $this->actingAs($this->user)
            ->get(route('videos.index', ['page' => 9999]));

        $response->assertRedirect();
        $this->assertStringNotContainsString('page=9999', $response->headers->get('Location'));
    }
}
