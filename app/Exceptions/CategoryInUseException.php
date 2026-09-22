<?php

namespace App\Exceptions;

use App\Models\Category;
use RuntimeException;

/**
 * Thrown when a category that still has transactions is deleted.
 *
 * A plain RuntimeException rather than an HttpException: turning this into a
 * response is the controller's job, so the service stays usable from a
 * command or a job that has no request to redirect.
 */
class CategoryInUseException extends RuntimeException
{
    /**
     * @param  Category  $category  The category that could not be deleted.
     */
    public function __construct(public readonly Category $category)
    {
        parent::__construct('This category still has transactions and cannot be deleted.');
    }
}
