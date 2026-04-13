<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'fieldKey'     => $this->resource['key'],
            'entityType'   => $this->resource['entity'],
            'isEnabled'    => $this->resource['is_enabled'],
            'label'        => $this->resource['label'],
            'type'         => $this->resource['type'],
            'options'      => $this->resource['options'],
            'industryHint' => $this->resource['industry_hint'],
        ];
    }
}
