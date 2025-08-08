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

    public function getAverageRating($userId)
    {
        $average = Rating::where('to_user_id', $userId)->avg('stars');

        return response()->json([
            'user_id' => $userId,
            'average_rating' => round($average, 2)
        ]);
    }
}
