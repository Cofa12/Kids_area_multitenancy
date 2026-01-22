<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Exceptions\NotFoundException;
use App\Http\Resources\V1\ChildPhotosResource;
use App\Http\Resources\V1\ShowAnalyticsResource;
use App\Http\Resources\V1\ShowChildPhoto;
use App\Models\ChildPhoto;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


/**
 * @psalm-suppress UnusedClass
 */

class DashboardController extends Controller
{
    public function acceptChildPhoto(string $id):JsonResponse
    {
        $childPhoto = ChildPhoto::find($id);
        if(!$childPhoto)
            throw new NotFoundException('Child Photo');

        $childPhoto->update(['isAccepted'=>true]);
        return response()->json(['message'=>'Photo accpeted Successfully'],JsonResponse::HTTP_OK);
    }

    public function rejectChildPhoto(string $id):JsonResponse
    {
        $childPhoto = ChildPhoto::find($id);
        if(!$childPhoto)
            throw new NotFoundException('Child Photo');

        $childPhoto->delete();
        return response()->json(['message'=>'Photo rejected Successfully'],JsonResponse::HTTP_OK);
    }

    public function getAnalytics()
    {
        return new ShowAnalyticsResource(User::all()->count(),Video::all()->count(),ChildPhoto::all()->count());
    }



    public function getUnAcceptedChildPhotos()
    {
        return ChildPhotosResource::collection(ChildPhoto::where('isAccepted',0)->paginate(10));
    }

    public function getAcceptedChildPhotos()
    {
        return ChildPhotosResource::collection(ChildPhoto::where('isAccepted',1)->paginate(10));
    }


    public function showChildPhoto(int $id)
    {
        $childPhoto = ChildPhoto::find($id);
        if (!$childPhoto)
            throw new NotFoundException('Child Photo');

        return new ShowChildPhoto($childPhoto);
    }

}
