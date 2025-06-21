<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Метод для отримання списку блог-постів для API.
     *
     * @return JsonResponse
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
     * Display the specified resource by slug.
     *
     * @param  string  $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $post = BlogPost::where('slug', $slug)
                ->with(['user:id,name', 'category:id,title,slug'])
                ->firstOrFail();

            return response()->json([
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content_raw' => $post->content_raw,
                'is_published' => $post->is_published,
                'published_at' => $post->published_at,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name
                ],
                'category' => [
                    'id' => $post->category->id,
                    'title' => $post->category->title,
                    'slug' => $post->category->slug
                ],
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Post not found.',
                'error' => 'Пост з таким slug не знайдено'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Створити новий пост
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:blog_posts',
                'category_id' => 'required|exists:blog_categories,id',
                'excerpt' => 'nullable|string|max:500',
                'content_raw' => 'required|string',
                'is_published' => 'boolean',
                'published_at' => 'nullable|date',
            ]);

            // Автоматично генеруємо slug якщо не вказано
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            // Встановлюємо користувача (поки що константа, пізніше auth)
            $validated['user_id'] = BlogPost::UNKNOWN_USER;

            // Якщо публікуємо, але не вказана дата - ставимо поточну
            if ($validated['is_published'] && empty($validated['published_at'])) {
                $validated['published_at'] = now();
            }

            $post = BlogPost::create($validated);
            $post->load(['user:id,name', 'category:id,title,slug']);

            return response()->json([
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content_raw' => $post->content_raw,
                'is_published' => $post->is_published,
                'published_at' => $post->published_at,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name
                ],
                'category' => [
                    'id' => $post->category->id,
                    'title' => $post->category->title,
                    'slug' => $post->category->slug
                ],
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Оновити пост
     *
     * @param Request $request
     * @param BlogPost $post
     * @return JsonResponse
     */
    public function update(Request $request, BlogPost $post): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:blog_posts,slug,' . $post->id,
                'category_id' => 'required|exists:blog_categories,id',
                'excerpt' => 'nullable|string|max:500',
                'content_raw' => 'required|string',
                'is_published' => 'boolean',
                'published_at' => 'nullable|date',
            ]);

            // Якщо публікуємо, але не вказана дата - ставимо поточну
            if ($validated['is_published'] && empty($validated['published_at'])) {
                $validated['published_at'] = now();
            }

            $post->update($validated);
            $post->load(['user:id,name', 'category:id,title,slug']);

            return response()->json([
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content_raw' => $post->content_raw,
                'is_published' => $post->is_published,
                'published_at' => $post->published_at,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name
                ],
                'category' => [
                    'id' => $post->category->id,
                    'title' => $post->category->title,
                    'slug' => $post->category->slug
                ],
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Видалити пост
     *
     * @param BlogPost $post
     * @return JsonResponse
     */
    public function destroy(BlogPost $post): JsonResponse
    {
        try {
            $post->delete();
            return response()->json(['message' => 'Post deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }
}
