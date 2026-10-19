<?php

namespace Modules\Academic\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

trait GuardsDeletion
{
    /**
     * Delete a record, turning a foreign-key violation (students, exams… still use it) into a friendly 422.
     */
    protected function deleteUnlessInUse(Model $model): JsonResponse
    {
        try {
            $model->delete();
        } catch (QueryException) {
            abort(422, __('academic::messages.in_use'));
        }

        return response()->json(null, 204);
    }
}
