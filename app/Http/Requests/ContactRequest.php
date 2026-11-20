<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates the public contact form. */
class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please tell us your name.',
            'email.required' => 'Please give us an email address so we can reply.',
            'email.email' => 'That email address doesn\'t look right.',
            'subject.required' => 'Please add a subject.',
            'message.required' => 'Please write your message.',
            'message.min' => 'Your message is a little short. Could you add some detail?',
        ];
    }
}
