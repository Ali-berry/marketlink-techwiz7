<?php

namespace App\Services\Agent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// AI jo naam bheje ("tomatoes", "Green Valley") us ka ek record dhoondta hai.
// Exact naam pehle, warna partial match sirf tab jab ek hi ho. Zyada match hon to options wapas jate hain.
class AgentNameMatcher
{
    private const MAX_OPTIONS_LISTED = 5;

    // model ya phir tool error array (ambiguous ho to "options" ke saath)
    public static function findOne(Builder $query, string $nameColumn, string $searchedName, string $whatIsBeingSearched): Model|array
    {
        $exactMatch = (clone $query)->whereRaw('LOWER('.$nameColumn.') = ?', [mb_strtolower(trim($searchedName))])->first();

        if ($exactMatch) {
            return $exactMatch;
        }

        $partialMatches = (clone $query)->where($nameColumn, 'like', '%'.trim($searchedName).'%')->take(self::MAX_OPTIONS_LISTED + 1)->get();

        if ($partialMatches->count() === 1) {
            return $partialMatches->first();
        }

        if ($partialMatches->isEmpty()) {
            return ['error' => "Couldn't find a {$whatIsBeingSearched} called \"{$searchedName}\"."];
        }

        return [
            'error' => "More than one {$whatIsBeingSearched} matches \"{$searchedName}\". Ask the user which one they mean - don't pick one yourself.",
            'options' => $partialMatches->take(self::MAX_OPTIONS_LISTED)->pluck($nameColumn)->all(),
        ];
    }
}
