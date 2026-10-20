<?php

namespace App\Http\Requests;

use App\Support\GolestanFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GolestanSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && (string) $this->user()->tenant_id === (string) tenant()?->id;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (GolestanFilters::fields() as $name => $field) {
            foreach ([$name, $name.'_to'] as $key) {
                if (is_string($this->input($key))) {
                    $normalized[$key] = strtr(trim($this->input($key)), array_combine(
                        preg_split('//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY),
                        str_split('01234567890123456789'),
                    ));
                }
            }
        }
        $this->merge($normalized);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = [];
        foreach (GolestanFilters::fields() as $name => $field) {
            $fieldRules = ['nullable', 'string', 'max:100'];
            if ($field['type'] === 'number') {
                $fieldRules[] = 'regex:/\A[0-9]{1,20}\z/';
            } elseif ($field['type'] === 'national_id') {
                $fieldRules[] = 'regex:/\A[0-9]{10}\z/';
            } elseif ($field['type'] === 'date') {
                $fieldRules[] = 'regex:/\A[0-9]{4}\/(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\z/';
            }
            $rules[$name] = $fieldRules;
            $rules[$name.'_to'] = $fieldRules;
        }

        return $rules;
    }

    /** @return array<int, \Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $validated = $validator->validated();
            if (collect($validated)->filter(fn (mixed $value): bool => filled($value))->isEmpty()) {
                $validator->errors()->add('filters', 'Enter at least one search criterion.');
            }
            foreach (GolestanFilters::fields() as $name => $field) {
                [$from, $to] = GolestanFilters::range($validated, $name);
                if ($from === '' || $to === '' || $field['type'] === 'text') {
                    continue;
                }
                $comparison = $field['type'] === 'date' ? strcmp($from, $to) : GolestanFilters::compareNumbers($from, $to);
                if ($comparison > 0) {
                    $validator->errors()->add($name.'_to', 'The end of the range must be greater than or equal to its start.');
                }
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $messages = ['*.string' => 'Enter a text value.', '*.max' => 'Values may not exceed 100 characters.'];
        foreach (GolestanFilters::fields() as $name => $field) {
            $message = match ($field['type']) {
                'national_id' => 'Enter a 10-digit national ID, including any leading zeros.',
                'date' => 'Use the Golestan date format YYYY/MM/DD (for example 1380/01/01).',
                default => 'Enter 1 to 20 digits.',
            };
            $messages[$name.'.regex'] = $message;
            $messages[$name.'_to.regex'] = $message;
        }

        return $messages;
    }
}
