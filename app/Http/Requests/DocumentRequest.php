<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Support\Crm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [$this->route('document') ? 'prohibited' : 'required', Rule::in(array_keys(Document::TYPES))],
            'company_id' => ['required', Crm::visibleCompanyRule()],
            'inquiry_id' => ['nullable', Rule::exists('inquiries', 'id')->where('company_id', $this->input('company_id'))],
            'direction' => ['required', Rule::in(['Received', 'Sent'])],
            'date' => ['nullable', 'date'],
            'ref' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Crm::DSTATUS)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'files' => ['nullable', 'array', 'max:10'],
            // both checks: `extensions` looks at the file name, `mimes` at the actual content
            'files.*' => ['file', 'max:20480', 'extensions:pdf,png,jpg,jpeg,webp,doc,docx,xls,xlsx,csv,txt', 'mimes:pdf,png,jpg,jpeg,webp,doc,docx,xls,xlsx,csv,txt'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.*.max' => 'Each file can be at most 20 MB.',
            'files.*.extensions' => 'Only PDF, image, Word, Excel, CSV or text files can be attached.',
            'files.*.mimes' => 'Only PDF, image, Word, Excel, CSV or text files can be attached.',
        ];
    }

    public function attributes(): array
    {
        return ['files.*' => 'file', 'company_id' => 'lead / customer', 'inquiry_id' => 'inquiry'];
    }
}
