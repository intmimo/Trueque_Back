<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\User;

class RatingController extends Controller
{
    public function rateUser(Request $request, $toUserId)
    {
        $request->validate([
            'stars' => 'required|integer|min:1|max:5'
        ]);

        $fromUser = $request->user();

        // Evitar que un usuario se califique a sí mismo
        if ($fromUser->id == $toUserId) {
            return response()->json(['message' => 'No puedes calificarte a ti mismo'], 400);
        }

        // Actualiza o crea la calificación
        $rating = Rating::updateOrCreate(
            ['from_user_id' => $fromUser->id, 'to_user_id' => $toUserId],
            ['stars' => $request->stars]
        );

        return response()->json(['message' => 'Calificación guardada', 'rating' => $rating]);
    }

    public function getAverageRating($toUserId){
    $average = Rating::where('to_user_id', $toUserId)->avg('stars');
    $count = Rating::where('to_user_id', $toUserId)->count();

    return response()->json([
        'user_id' => $toUserId,
        'average_rating' => round($average, 2),
        'rating_count' => $count
    ]);
}

public function getRatingHistory($toUserId)
{
    $ratings = Rating::where('to_user_id', $toUserId)
        ->with('fromUser:id,name') // trae info básica del usuario que calificó
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'user_id' => $toUserId,
        'ratings' => $ratings
    ]);
}

}
