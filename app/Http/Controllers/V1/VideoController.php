<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Exceptions\NotFoundException;
use App\Http\Requests\V1\VideoCreationRequest;
use App\Http\Requests\V1\VideoUpdateRequest;
use App\Http\Resources\V1\ShowSingleVideoResource;
use App\Http\Resources\V1\RandomVideoResource;
use App\Http\Resources\V1\VideoResource;
use App\Models\Video;
use App\Services\V1\FileHandling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-suppress UnusedClass
 */

class VideoController extends Controller
{

    public function __construct(readonly private FileHandling $fileHandling)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $lang = $request->header('Accept-Language');
        $lang = $lang ? substr($lang, 0, 2) : 'en';

        $query = Video::query()->whereNotNull(['title_' . $lang]);

        $sort = $request->query('sort');
        if ($sort === 'newest') {
            $query->orderBy('created_at', 'desc');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at');
        }

        return VideoResource::collection($query->paginate(10));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(VideoCreationRequest $request): JsonResponse
    {
        $dataWithoutThumbnail = $request->except('thumbnail_url_en', 'thumbnail_url_ar');
        $thumbnails = ['thumbnail_url_en' => $request->file('thumbnail_url_en'), 'thumbnail_url_ar' => $request->file('thumbnail_url_ar')];

        foreach ($thumbnails as $key => $thumbnail) {
            if (!$thumbnail)
                continue;
            $dataWithoutThumbnail[$key] = $this->fileHandling->upload($thumbnail, 'thumbnails');
        }

        $dataWithoutThumbnail['user_id'] = Auth::guard('admin')->id();

        Video::create($dataWithoutThumbnail);
        return response()->json(['message' => 'Video Created Successfully'], Response::HTTP_CREATED);
    }
    /**
     * Search in all videos by name/title.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $searchable_key = $request->query('q');
        $columnToSearchedAt = 'title_' . app()->getLocale();

        $builder = Video::query();
        if ($searchable_key !== '') {
            $builder->where(function ($sub) use ($searchable_key, $columnToSearchedAt) {
                $sub->where($columnToSearchedAt, 'like', "%$searchable_key%");
            });
        }

        return VideoResource::collection($builder->paginate(10));
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $video = Video::find($id);
        if (!$video)
            throw new NotFoundException('Video');

        return new ShowSingleVideoResource($video);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(VideoUpdateRequest $request, Video $video)
    {
        $dataWithoutThumbnail = $request->except('thumbnail_url_en', 'thumbnail_url_ar');
        if ($request->hasFile('thumbnail_url_en') || $request->hasFile('thumbnail_url_ar')) {
            if ($video->thumbnail_url_en) {
                $this->fileHandling->delete($video->thumbnail_url_en);
            }
            if ($video->thumbnail_url_ar) {
                $this->fileHandling->delete($video->thumbnail_url_ar);
            }

            $thumbnails = ['thumbnail_url_en' => $request->file('thumbnail_url_en'), 'thumbnail_url_ar' => $request->file('thumbnail_url_ar')];

            foreach ($thumbnails as $key => $thumbnail) {
                if (!$thumbnail)
                    continue;
                $dataWithoutThumbnail[$key] = $this->fileHandling->upload($thumbnail, 'thumbnails');
            }
        }

        $video->update($dataWithoutThumbnail);
        return response()->json(['message' => 'Video Updated Successfully'], Response::HTTP_OK);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $video = Video::find($id);

        if (!$video)
            throw new NotFoundException('Video');

        $neededToBeDeleted = ['thumbnail_url_en', 'thumbnail_url_ar'];
        foreach ($neededToBeDeleted as $key) {
            if ($video->$key)
                $this->fileHandling->delete($video->$key);
        }

        $video->delete();
        return response()->json(['message' => 'Video Deleted Successfully'], Response::HTTP_OK);
    }
    /**
     * Display a listing of the resource in random order.
     */
    public function randomVideos(Request $request): AnonymousResourceCollection
    {
        $lang = $request->header('Accept-Language');
        $lang = $lang ? substr($lang, 0, 2) : 'en';

        $query = Video::query()
            ->whereNotNull('title_' . $lang)
            ->inRandomOrder();

        return RandomVideoResource::collection($query->paginate(10));
    }
}
