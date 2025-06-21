<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Метод для отримання списку категорій для API.
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        /** @var LengthAwarePaginator $categories */
        $categories = BlogCategory::with(['parentCategory:id,title'])
            ->withCount('posts')
            ->orderBy('id', 'DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedCategories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent_title' => $category->parent_title, // Використовуємо аксесор
                'posts_count' => $category->posts_count,
                'is_root' => $category->isRoot(), // Використовуємо метод
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ];
        });

        return response()->json([
            'data' => $formattedCategories,
            'meta' => [
                'current_page' => $categories->currentPage(),
                'from' => $categories->firstItem(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'to' => $categories->lastItem(),
                'total' => $categories->total(),
            ],
            'links' => [
                'first' => $categories->url(1),
                'last' => $categories->url($categories->lastPage()),
                'prev' => $categories->previousPageUrl(),
                'next' => $categories->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Отримати всі категорії без пагінації (для селектів)
     *
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        try {
            Log::info('CategoryController::all() викликано');

            $categories = BlogCategory::with(['parentCategory:id,title'])
                ->withCount('posts')
                ->orderBy('title')
                ->get();

            Log::info('Знайдено категорій: ' . $categories->count());

            $formattedCategories = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id,
                    'parent_title' => $category->parent_title,
                    'posts_count' => $category->posts_count,
                    'is_root' => $category->isRoot(),
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ];
            });

            return response()->json($formattedCategories);
        } catch (\Exception $e) {
            Log::error('Помилка в CategoryController::all(): ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
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
            Log::info("CategoryController::show() викликано для slug: {$slug}");

            $category = BlogCategory::where('slug', $slug)
                ->with(['parentCategory:id,title,slug'])
                ->withCount('posts')
                ->firstOrFail();

            Log::info('Категорію знайдено: ' . $category->title);

            return response()->json([
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent_title' => $category->parent_title,
                'posts_count' => $category->posts_count,
                'is_root' => $category->isRoot(),
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning("Категорію не знайдено для slug: {$slug}");
            return response()->json([
                'message' => 'Category not found.',
                'error' => 'Категорію з таким slug не знайдено'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Помилка в CategoryController::show(): ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Створити нову категорію
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:blog_categories',
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|exists:blog_categories,id',
            ]);

            // Автоматично генеруємо slug якщо не вказано
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            // Перевіряємо унікальність slug
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (BlogCategory::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            $category = BlogCategory::create($validated);
            $category->load(['parentCategory:id,title']);
            $category->loadCount('posts');

            return response()->json([
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent_title' => $category->parent_title,
                'posts_count' => $category->posts_count,
                'is_root' => $category->isRoot(),
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Оновити категорію
     *
     * @param Request $request
     * @param BlogCategory $category
     * @return JsonResponse
     */
    public function update(Request $request, BlogCategory $category): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:blog_categories,slug,' . $category->id,
                'description' => 'nullable|string|max:1000',
                'parent_id' => 'nullable|exists:blog_categories,id',
            ]);

            // Перевіряємо, щоб категорія не стала батьківською сама для себе
            if (!empty($validated['parent_id']) && $validated['parent_id'] == $category->id) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => ['parent_id' => ['Категорія не може бути батьківською для самої себе']]
                ], 422);
            }

            $category->update($validated);
            $category->load(['parentCategory:id,title']);
            $category->loadCount('posts');

            return response()->json([
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent_title' => $category->parent_title,
                'posts_count' => $category->posts_count,
                'is_root' => $category->isRoot(),
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Видалити категорію
     *
     * @param BlogCategory $category
     * @return JsonResponse
     */
    public function destroy(BlogCategory $category): JsonResponse
    {
        try {
            // Перевіряємо чи це не коренева категорія
            if ($category->isRoot()) {
                return response()->json([
                    'message' => 'Cannot delete root category.',
                    'error' => 'Неможливо видалити кореневу категорію'
                ], 422);
            }

            // Перевіряємо чи немає постів в категорії
            $postsCount = $category->posts()->count();
            if ($postsCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete category with posts.',
                    'error' => 'Неможливо видалити категорію з постами'
                ], 422);
            }

            // Перевіряємо чи немає дочірніх категорій
            $childrenCount = BlogCategory::where('parent_id', $category->id)->count();
            if ($childrenCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete category with children.',
                    'error' => 'Неможливо видалити категорію з дочірніми категоріями'
                ], 422);
            }

            $category->delete();
            return response()->json(['message' => 'Category deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отримати пости категорії
     *
     * @param string $slug
     * @param Request $request
     * @return JsonResponse
     */
    public function posts(string $slug, Request $request): JsonResponse
    {
        try {
            $category = BlogCategory::where('slug', $slug)->firstOrFail();

            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $posts = $category->posts()
                ->with(['user:id,name', 'category:id,title,slug'])
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
                'category' => [
                    'id' => $category->id,
                    'title' => $category->title,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category not found.',
                'error' => 'Категорію з таким slug не знайдено'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
