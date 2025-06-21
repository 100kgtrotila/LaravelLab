<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PostController extends Controller
{
    /**
     * Display a paginated list of blog posts.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        /** @var LengthAwarePaginator $posts */
        $posts = BlogPost::with(['user:id,name', 'category:id,title,slug'])
            ->orderBy('id', 'DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedPosts = $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'is_published' => $post->is_published,
                'published_at' => $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d.M H:i') : '',
                'user' => ['name' => $post->user->name],
                'category' => [
                    'title' => $post->category->title,
                    'slug' => $post->category->slug
                ],
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        });

        return response()->json([
            'data' => $formattedPosts,
            'meta' => [
                'current_page' => $posts->currentPage(),
                'from' => $posts->firstItem(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'to' => $posts->lastItem(),
                'total' => $posts->total(),
            ],
            'links' => [
                'first' => $posts->url(1),
                'last' => $posts->url($posts->lastPage()),
                'prev' => $posts->previousPageUrl(),
                'next' => $posts->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created blog post in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:blog_posts,slug',
                'category_id' => 'required|integer|exists:blog_categories,id',
                'excerpt' => 'nullable|string|max:500',
                'content_raw' => 'required|string',
                'is_published' => 'required|boolean',
                'published_at' => 'nullable|date',
            ]);

            // Якщо slug не надано, генеруємо його з назви
            if (empty($validatedData['slug'])) {
                $validatedData['slug'] = Str::slug($validatedData['title']);
            }
            // Перевіряємо унікальність slug і додаємо суфікс, якщо потрібно
            $originalSlug = $validatedData['slug'];
            $counter = 1;
            while (BlogPost::where('slug', $validatedData['slug'])->exists()) {
                $validatedData['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Встановлюємо дату публікації, якщо пост публікується
            if ($validatedData['is_published'] && empty($validatedData['published_at'])) {
                $validatedData['published_at'] = now();
            } elseif (!$validatedData['is_published']) {
                $validatedData['published_at'] = null;
            }

            // Встановлюємо ID користувача
            $validatedData['user_id'] = BlogPost::UNKNOWN_USER;

            $post = BlogPost::create($validatedData);

            return response()->json($post->load(['user:id,name', 'category:id,title,slug']), 201);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error in PostController@store: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred on the server.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified blog post by slug.
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $post = BlogPost::where('slug', $slug)
                ->with(['user:id,name', 'category:id,title,slug'])
                ->firstOrFail();

            return response()->json($post);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Post not found.'], 404);
        } catch (\Exception $e) {
            Log::error("Error in PostController@show for slug {$slug}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified blog post in storage.
     */
    public function update(Request $request, BlogPost $post): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                // Правило унікальності для slug, що ігнорує поточний пост
                'slug' => 'required|string|max:255|unique:blog_posts,slug,' . $post->id,
                'category_id' => 'required|integer|exists:blog_categories,id',
                'excerpt' => 'nullable|string|max:500',
                'content_raw' => 'required|string',
                'is_published' => 'required|boolean',
                'published_at' => 'nullable|date',
            ]);

            // Оновлюємо дату публікації за аналогією з методом store
            if ($validatedData['is_published'] && empty($validatedData['published_at'])) {
                // Якщо пост опубліковано, але дата не встановлена, ставимо поточну
                $validatedData['published_at'] = now();
            } elseif (!$validatedData['is_published']) {
                // Якщо пост знято з публікації, обнуляємо дату
                $validatedData['published_at'] = null;
            }

            $post->update($validatedData);

            return response()->json($post->load(['user:id,name', 'category:id,title,slug']));

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Error in PostController@update for post ID {$post->id}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred on the server.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified blog post from storage.
     */
    public function destroy(BlogPost $post): JsonResponse
    {
        try {
            $post->delete(); // Виконує soft delete завдяки трейту в моделі
            return response()->json(['message' => 'Post moved to trash successfully.']);
        } catch (\Exception $e) {
            Log::error("Error in PostController@destroy for post ID {$post->id}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while deleting the post.'], 500);
        }
    }
}
