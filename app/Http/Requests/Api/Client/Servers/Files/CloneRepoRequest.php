<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class CloneRepoRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_CREATE;
    }

    public function rules(): array
    {
        return [
            'repo_url' => 'required|string|url|max:500',
            'branch'   => 'nullable|string|max:255|regex:/^[A-Za-z0-9._\/\-]+$/',
            'directory'=> 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'repo_url.required' => 'A repository URL is required.',
            'repo_url.url'      => 'That does not look like a valid URL.',
            'branch.regex'      => 'Branch names may only contain letters, numbers, dots, dashes, slashes and underscores.',
        ];
    }
}
