<?php

namespace App\Http\Requests\Repositories;

use App\Models\Repository;
use Illuminate\Foundation\Http\FormRequest;

class RestoreRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $repository = $this->route('repository');

        return $repository instanceof Repository && $this->user()?->can('restore', $repository) === true;
    }
}
