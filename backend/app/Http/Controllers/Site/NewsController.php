<?php

namespace App\Http\Controllers\Site;

use App\Http\Resources\ArticleListResource;
use App\Http\Resources\ArticleResource;
use App\Models\Page;
use App\Models\Tag;
use App\Services\ArticleService;
use App\Services\CategoryService;
use App\Services\HomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ArticleService $articleService,
        private readonly HomeService $homeService,
    ) {}

    public function category(Request $request, string $category): JsonResponse
    {
        $cat = $this->categoryService->findBySlug($category);
        $subcategory_slug = $request->query('subcategory');

        $paginated = $subcategory_slug
            ? $this->articleService->getPaginatedBySubcategory($subcategory_slug)
            : $this->articleService->getPaginatedByCategory($category);

        return $this->respondWithJson([
            'category' => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'color' => $cat->color,
                'subcategories' => $cat->subcategories->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'slug' => $s->slug,
                ]),
            ],
            'featured_block' => $this->homeService->buildFeaturedBlock("category:{$category}"),
            'articles' => [
                'data' => ArticleListResource::collection($paginated->items()),
                'pagination' => $this->paginationMeta($paginated),
            ],
        ]);
    }

    public function subcategoryNews(string $category, string $subcategory): JsonResponse
    {
        $articles = $this->articleService->getBySubcategory($subcategory);

        return $this->respondWithJson(
            ArticleListResource::collection($articles)
        );
    }

    public function subcategoryArticles(string $category, string $subcategory): JsonResponse
    {
        $paginated = $this->articleService->getPaginatedBySubcategory($subcategory);

        return $this->respondWithJson([
            'data' => ArticleListResource::collection($paginated->items()),
            'pagination' => $this->paginationMeta($paginated),
        ]);
    }

    public function article(Request $request, string $category, string $subcategory, string $slug): JsonResponse
    {
        $article = $this->articleService->findBySlug($slug);

        $this->articleService->incrementViews($article, $request->ip());

        return $this->respondWithJson([
            'article' => new ArticleResource($article),
        ]);
    }

    public function page(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->where('active', true)->firstOrFail();

        return $this->respondWithJson($page->only('id', 'slug', 'title', 'content'));
    }

    public function search(Request $request): JsonResponse
    {
        $query = trim($request->query('q', ''));

        if ($query === '') {
            return $this->respondWithJson([
                'data' => [],
                'pagination' => ['current_page' => 1, 'last_page' => 1, 'total' => 0, 'prev_page_url' => null, 'next_page_url' => null],
            ]);
        }

        $paginated = $this->articleService->searchPaginated($query);

        return $this->respondWithJson([
            'data' => ArticleListResource::collection($paginated->items()),
            'pagination' => $this->paginationMeta($paginated),
        ]);
    }

    public function archive(int $year, int $month): JsonResponse
    {
        $label = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $paginated = $this->articleService->getPaginatedByArchive($year, $month);

        return $this->respondWithJson([
            'label' => $label,
            'data' => ArticleListResource::collection($paginated->items()),
            'pagination' => $this->paginationMeta($paginated),
        ]);
    }

    public function tag(string $slug): JsonResponse
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $paginated = $this->articleService->getPaginatedByTag($slug);

        return $this->respondWithJson([
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
            'data' => ArticleListResource::collection($paginated->items()),
            'pagination' => $this->paginationMeta($paginated),
        ]);
    }
}
