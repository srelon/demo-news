<?php

namespace Tests\Feature\Site;

use App\Models\Admin\AdminModeratorAccount;
use App\Models\Admin\AdminUsers;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Helpers\AdminTestHelper;
use Tests\Helpers\SiteTestHelper;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RefreshDatabase, SiteTestHelper, AdminTestHelper;

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'public_id' => 'usr_' . uniqid(),
            'name' => 'Test User',
            'email' => 'user_' . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
        ], $overrides));
    }

    private function createComment(User $user, array $overrides = []): Comment
    {
        $article = $this->createPublishedArticle();

        return Comment::create(array_merge([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'body' => 'Test comment body',
            'status' => 1,
        ], $overrides));
    }

    private function createModeratorUser(): array
    {
        $site_user = $this->createUser();
        $admin = $this->adminWithFull('moderator');

        AdminModeratorAccount::create([
            'admin_user_id' => $admin->id,
            'user_id' => $site_user->id,
        ]);

        // Reset static cache so the new record is picked up
        AdminModeratorAccount::resetUserIdsCache();

        return [$site_user, $admin];
    }

    // ── List comments ─────────────────────────────────────────────────────────

    public function test_guest_can_list_comments(): void
    {
        $article = $this->createPublishedArticle();

        $this->getJson("/api/comments/{$article->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['comments', 'pagination']]);
    }

    public function test_comments_return_pagination(): void
    {
        $user = $this->createUser();
        $article = $this->createPublishedArticle();

        for ($i = 0; $i < 3; $i++) {
            Comment::create(['article_id' => $article->id, 'user_id' => $user->id, 'body' => "Comment $i", 'status' => 1]);
        }

        $this->getJson("/api/comments/{$article->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 3);
    }

    // ── List replies ──────────────────────────────────────────────────────────

    public function test_guest_can_list_replies(): void
    {
        $user = $this->createUser();
        $parent = $this->createComment($user);

        Comment::create([
            'article_id' => $parent->article_id,
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'body' => 'Reply body',
            'status' => 1,
        ]);

        $this->getJson("/api/comments/{$parent->id}/replies")
            ->assertStatus(200)
            ->assertJsonPath('data.replies.0.body', 'Reply body');
    }

    // ── Create comment ────────────────────────────────────────────────────────

    public function test_authenticated_user_can_post_comment(): void
    {
        $user = $this->createUser();
        $article = $this->createPublishedArticle();

        $this->actingAs($user, 'web')
            ->postJson('/api/comments', [
                'article_id' => $article->id,
                'body' => 'Hello world',
            ])
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['comment']]);

        $this->assertDatabaseHas('comments', [
            'article_id' => $article->id,
            'user_id' => $user->id,
            'body' => 'Hello world',
        ]);
    }

    public function test_guest_cannot_post_comment(): void
    {
        $article = $this->createPublishedArticle();

        $this->postJson('/api/comments', [
            'article_id' => $article->id,
            'body' => 'Hello world',
        ])->assertStatus(401);
    }

    public function test_comment_requires_body(): void
    {
        $user = $this->createUser();
        $article = $this->createPublishedArticle();

        $this->actingAs($user, 'web')
            ->postJson('/api/comments', ['article_id' => $article->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_comment_strips_dangerous_tags(): void
    {
        $user = $this->createUser();
        $article = $this->createPublishedArticle();

        $this->actingAs($user, 'web')
            ->postJson('/api/comments', [
                'article_id' => $article->id,
                'body' => '<script>alert(1)</script><b>bold</b>',
            ])
            ->assertStatus(200);

        // strip_tags removes <script> tag but keeps its text content
        $comment = Comment::where('user_id', $user->id)->first();
        $this->assertStringNotContainsString('<script>', $comment->body);
        $this->assertStringContainsString('alert(1)', $comment->body);
        $this->assertStringContainsString('<b>bold</b>', $comment->body);
    }

    public function test_user_can_post_reply(): void
    {
        $user = $this->createUser();
        $parent = $this->createComment($user);

        $this->actingAs($user, 'web')
            ->postJson('/api/comments', [
                'article_id' => $parent->article_id,
                'body' => 'A reply',
                'parent_id' => $parent->id,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'parent_id' => $parent->id,
            'body' => 'A reply',
        ]);
    }

    // ── Update comment ────────────────────────────────────────────────────────

    public function test_user_can_edit_own_recent_comment(): void
    {
        $user = $this->createUser();
        $comment = $this->createComment($user);

        $this->actingAs($user, 'web')
            ->patchJson("/api/comments/{$comment->id}", ['body' => 'Updated body'])
            ->assertStatus(200)
            ->assertJsonPath('data.body', 'Updated body');

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Updated body']);
    }

    public function test_user_cannot_edit_another_users_comment(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $comment = $this->createComment($owner);

        $this->actingAs($other, 'web')
            ->patchJson("/api/comments/{$comment->id}", ['body' => 'Hijack'])
            ->assertStatus(404);
    }

    public function test_user_cannot_edit_comment_older_than_one_day(): void
    {
        $user = $this->createUser();
        $comment = $this->createComment($user);

        DB::table('comments')
            ->where('id', $comment->id)
            ->update(['created_at' => now()->subDays(2)]);

        $this->actingAs($user, 'web')
            ->patchJson("/api/comments/{$comment->id}", ['body' => 'Too late'])
            ->assertStatus(403);
    }

    public function test_edit_strips_dangerous_tags(): void
    {
        $user = $this->createUser();
        $comment = $this->createComment($user);

        $this->actingAs($user, 'web')
            ->patchJson("/api/comments/{$comment->id}", [
                'body' => '<script>evil()</script><i>italic</i>',
            ])
            ->assertStatus(200);

        // strip_tags removes the <script> tag but keeps its text content — expected behaviour
        $this->assertDatabaseMissing('comments', ['body' => '<script>evil()</script><i>italic</i>']);
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'evil()<i>italic</i>']);
    }

    public function test_guest_cannot_edit_comment(): void
    {
        $user = $this->createUser();
        $comment = $this->createComment($user);

        $this->patchJson("/api/comments/{$comment->id}", ['body' => 'Hacked'])
            ->assertStatus(401);
    }

    // ── Delete comment (user self-delete) ─────────────────────────────────────

    public function test_user_can_delete_own_comment(): void
    {
        $user = $this->createUser();
        $comment = $this->createComment($user);

        $this->actingAs($user, 'web')
            ->deleteJson("/api/comments/{$comment->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.ok', true);

        // Soft delete: record stays with status=2, deleted_at remains null
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 2]);
    }

    public function test_user_self_delete_does_not_remove_replies(): void
    {
        $user = $this->createUser();
        $parent = $this->createComment($user);

        $reply = Comment::create([
            'article_id' => $parent->article_id,
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'body' => 'Reply stays',
            'status' => 1,
        ]);

        $this->actingAs($user, 'web')
            ->deleteJson("/api/comments/{$parent->id}")
            ->assertStatus(200);

        // Parent is soft-deleted, reply is untouched
        $this->assertDatabaseHas('comments', ['id' => $parent->id, 'status' => 2]);
        $this->assertDatabaseHas('comments', ['id' => $reply->id, 'status' => 1]);
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $comment = $this->createComment($owner);

        $this->actingAs($other, 'web')
            ->deleteJson("/api/comments/{$comment->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 1]);
    }

    public function test_guest_cannot_delete_comment(): void
    {
        $user = $this->createUser();
        $comment = $this->createComment($user);

        $this->deleteJson("/api/comments/{$comment->id}")
            ->assertStatus(401);
    }

    // ── Moderator delete ──────────────────────────────────────────────────────

    public function test_moderator_deleting_regular_user_comment_sets_status_3(): void
    {
        [$moderator_user] = $this->createModeratorUser();
        $regular_user = $this->createUser();
        $comment = $this->createComment($regular_user);

        $this->actingAs($moderator_user, 'web')
            ->deleteJson("/api/comments/{$comment->id}/moderate")
            ->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 3,
            'deleted_by' => 1,
        ]);
        $this->assertNull($comment->fresh()->deleted_at);
    }

    public function test_moderator_deleting_another_moderator_comment_sets_deleted_at(): void
    {
        [$acting_moderator] = $this->createModeratorUser();
        [$target_moderator] = $this->createModeratorUser();
        $comment = $this->createComment($target_moderator);

        $this->actingAs($acting_moderator, 'web')
            ->deleteJson("/api/comments/{$comment->id}/moderate")
            ->assertStatus(200);

        $updated = Comment::withTrashed()->find($comment->id);
        $this->assertNotNull($updated->deleted_at);
        $this->assertEquals(3, $updated->status);
        $this->assertEquals(1, $updated->deleted_by);
    }

    public function test_moderator_deleting_own_comment_sets_deleted_at(): void
    {
        [$moderator_user] = $this->createModeratorUser();
        $comment = $this->createComment($moderator_user);

        $this->actingAs($moderator_user, 'web')
            ->deleteJson("/api/comments/{$comment->id}/moderate")
            ->assertStatus(200);

        $updated = Comment::withTrashed()->find($comment->id);
        $this->assertNotNull($updated->deleted_at);
        $this->assertEquals(3, $updated->status);
    }

    public function test_moderator_delete_also_soft_deletes_replies(): void
    {
        [$moderator_user] = $this->createModeratorUser();
        $comment = $this->createComment($moderator_user);

        $reply = Comment::create([
            'article_id' => $comment->article_id,
            'user_id' => $this->createUser()->id,
            'parent_id' => $comment->id,
            'body' => 'reply',
            'status' => 1,
        ]);

        $this->actingAs($moderator_user, 'web')
            ->deleteJson("/api/comments/{$comment->id}/moderate")
            ->assertStatus(200);

        $updated_reply = Comment::withTrashed()->find($reply->id);
        $this->assertNotNull($updated_reply->deleted_at);
        $this->assertEquals(3, $updated_reply->status);
    }

    public function test_soft_deleted_moderator_comment_not_visible_on_site(): void
    {
        [$moderator_user] = $this->createModeratorUser();
        $comment = $this->createComment($moderator_user);
        $article_id = $comment->article_id;

        $this->actingAs($moderator_user, 'web')
            ->deleteJson("/api/comments/{$comment->id}/moderate");

        $response = $this->getJson("/api/comments/{$article_id}")
            ->assertStatus(200);

        $ids = collect($response->json('data.comments'))->pluck('id')->all();
        $this->assertNotContains($comment->id, $ids);
    }

    // ── Like / dislike ────────────────────────────────────────────────────────

    public function test_user_can_like_comment(): void
    {
        $user = $this->createUser();
        $owner = $this->createUser();
        $comment = $this->createComment($owner);

        $this->actingAs($user, 'web')
            ->postJson("/api/comments/{$comment->id}/like", ['opp_type' => 2])
            ->assertStatus(200)
            ->assertJsonPath('data.likes', 1)
            ->assertJsonPath('data.dislikes', 0);
    }

    public function test_user_can_dislike_comment(): void
    {
        $user = $this->createUser();
        $owner = $this->createUser();
        $comment = $this->createComment($owner);

        $this->actingAs($user, 'web')
            ->postJson("/api/comments/{$comment->id}/like", ['opp_type' => 1])
            ->assertStatus(200)
            ->assertJsonPath('data.likes', 0)
            ->assertJsonPath('data.dislikes', 1);
    }

    public function test_user_can_cancel_like(): void
    {
        $user = $this->createUser();
        $owner = $this->createUser();
        $comment = $this->createComment($owner);

        $this->actingAs($user, 'web')
            ->postJson("/api/comments/{$comment->id}/like", ['opp_type' => 2]);

        $result = $this->actingAs($user, 'web')
            ->postJson("/api/comments/{$comment->id}/like", ['opp_type' => 2])
            ->assertStatus(200);

        $this->assertEquals(0, $result->json('data.opp_type'));
        $this->assertEquals(0, $result->json('data.likes'));
    }

    public function test_like_switches_from_dislike_to_like(): void
    {
        $user = $this->createUser();
        $owner = $this->createUser();
        $comment = $this->createComment($owner);

        $this->actingAs($user, 'web')
            ->postJson("/api/comments/{$comment->id}/like", ['opp_type' => 1]);

        $this->actingAs($user, 'web')
            ->postJson("/api/comments/{$comment->id}/like", ['opp_type' => 2])
            ->assertStatus(200)
            ->assertJsonPath('data.likes', 1)
            ->assertJsonPath('data.dislikes', 0);
    }

    public function test_guest_cannot_like(): void
    {
        $user = $this->createUser();
        $comment = $this->createComment($user);

        $this->postJson("/api/comments/{$comment->id}/like", ['opp_type' => 2])
            ->assertStatus(401);
    }

    public function test_like_requires_valid_opp_type(): void
    {
        $user = $this->createUser();
        $comment = $this->createComment($user);

        $this->actingAs($user, 'web')
            ->postJson("/api/comments/{$comment->id}/like", ['opp_type' => 0])
            ->assertStatus(422);
    }

    // ── Report ────────────────────────────────────────────────────────────────

    public function test_user_can_report_comment(): void
    {
        $user = $this->createUser();
        $owner = $this->createUser();
        $comment = $this->createComment($owner);

        $this->actingAs($user, 'web')
            ->postJson("/api/comments/{$comment->id}/report", ['reason' => 'Spam'])
            ->assertStatus(200)
            ->assertJsonPath('data.reported', true);

        $this->assertDatabaseHas('comment_reports', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'reason' => 'Spam',
        ]);
    }

    public function test_report_without_reason_is_allowed(): void
    {
        $user = $this->createUser();
        $owner = $this->createUser();
        $comment = $this->createComment($owner);

        $this->actingAs($user, 'web')
            ->postJson("/api/comments/{$comment->id}/report")
            ->assertStatus(200);
    }

    public function test_guest_cannot_report(): void
    {
        $user = $this->createUser();
        $comment = $this->createComment($user);

        $this->postJson("/api/comments/{$comment->id}/report", ['reason' => 'Spam'])
            ->assertStatus(401);
    }
}
