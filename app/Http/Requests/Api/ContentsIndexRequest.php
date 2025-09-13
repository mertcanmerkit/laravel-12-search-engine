<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ContentsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'        => ['nullable','string'],
            'type'     => ['nullable','in:video,article'],
            'sort'     => ['nullable','in:final_score,published_at,title,type'],
            'order'    => ['nullable','in:asc,desc'],
            'page'     => ['nullable','integer','min:1'],
            'per_page' => ['nullable','integer','min:1','max:100'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $v = parent::validated();
        $v['q']        = $v['q']   ?? '';
        $v['type']     = $v['type']?? null;
        $v['sort']     = $v['sort']?? 'final_score';
        $v['order']    = $v['order']?? 'desc';
        $v['page']     = (int)($v['page'] ?? 1);
        $v['per_page'] = (int)($v['per_page'] ?? 20);
        return $v;
    }
}
