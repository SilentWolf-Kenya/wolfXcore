<?php

namespace Pterodactyl\Http\Requests\Admin\Settings;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class AdvancedSettingsFormRequest extends AdminFormRequest
{
    /**
     * Return all the rules to apply to this request's data.
     */
    public function rules(): array
    {
        return [
            'recaptcha:enabled' => 'required|in:true,false',
            'recaptcha:secret_key' => 'required|string|max:191',
            'recaptcha:website_key' => 'required|string|max:191',
            'wolfxcore:guzzle:timeout' => 'required|integer|between:1,60',
            'wolfxcore:guzzle:connect_timeout' => 'required|integer|between:1,60',
            'wolfxcore:client_features:allocations:enabled' => 'required|in:true,false',
            'wolfxcore:client_features:allocations:range_start' => [
                'nullable',
                'required_if:wolfxcore:client_features:allocations:enabled,true',
                'integer',
                'between:1024,65535',
            ],
            'wolfxcore:client_features:allocations:range_end' => [
                'nullable',
                'required_if:wolfxcore:client_features:allocations:enabled,true',
                'integer',
                'between:1024,65535',
                'gt:wolfxcore:client_features:allocations:range_start',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'recaptcha:enabled' => 'reCAPTCHA Enabled',
            'recaptcha:secret_key' => 'reCAPTCHA Secret Key',
            'recaptcha:website_key' => 'reCAPTCHA Website Key',
            'wolfxcore:guzzle:timeout' => 'HTTP Request Timeout',
            'wolfxcore:guzzle:connect_timeout' => 'HTTP Connection Timeout',
            'wolfxcore:client_features:allocations:enabled' => 'Auto Create Allocations Enabled',
            'wolfxcore:client_features:allocations:range_start' => 'Starting Port',
            'wolfxcore:client_features:allocations:range_end' => 'Ending Port',
        ];
    }
}
