<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ReportPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReportScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->period) {
            $this->merge(['period' => strtolower($this->period)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'period' => ['required', Rule::enum(ReportPeriod::class)],
            'keywords' => ['required', 'array'],
            'keywords.*' => ['string'],
        ];
    }
}
