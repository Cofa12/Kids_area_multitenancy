<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Exceptions\NotFoundException;
use App\Http\Resources\V1\CategoriesWithVideosResource;
use App\Http\Resources\V1\CategoryResource;
use App\Http\Resources\V1\ShowVideosOfCategory;
use App\Http\Resources\V1\VideoResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;


/**
 * @psalm-suppress UnusedClass
 */

class CategoryController extends Controller
{
    public function __construct()
    {
        DB::setDefaultConnection('landlord');

    }

    public function index()
    {
        return CategoryResource::collection(Category::all());
    }
    public function show(Request $request, string $id)
    {
        $lang = $request->header('Accept-Language');
        $category = Category::with(['videos'])->find($id);
        if(!$category)
            throw new NotFoundException('Category');


        $sort = $request->query('sort');
        if ($sort === 'newest' || $sort === 'oldest') {
            $order = $sort === 'newest' ? 'desc' : 'asc';
            $videos = $category->videos()->whereNotNull('title_'.$lang)
                ->orderBy('created_at', $order)
                ->paginate(10);

            return VideoResource::collection($videos);
        }

        return new ShowVideosOfCategory($category);
    }

    public function getCategoriesWithVideos():AnonymousResourceCollection
    {
        return CategoriesWithVideosResource::collection(Category::with(['videos'])->get());
    }

    public function searchVideos(Request $request, string $id)
    {
        $category = Category::find($id);
        if(!$category)
            throw new NotFoundException('Category');

        $searchable_key  = $request->query('q');
        $columnToSearchedAt = 'title_'.app()->getLocale();

        $videos = $category->videos()
            ->when($searchable_key !== '', function ($query) use ($searchable_key, $columnToSearchedAt) {
                $query->where(function ($sub) use ($searchable_key, $columnToSearchedAt) {
                    $sub->where($columnToSearchedAt, 'like', "%$searchable_key%");
                });
            })
            ->paginate(10);

        return VIdeoResource::collection($videos);
    }
}
