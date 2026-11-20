<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

/** An author's public page with their published articles and Person structured data. */
class ShowAuthor extends Controller
{
    public function __invoke(string $slug): View
    {
        $author = User::where('slug', $slug)->firstOrFail();

        abort_unless($author->hasAuthorPage(), 404);

        $posts = $author->posts()
            ->published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate(12);

        return view('authors.show', [
            'author' => $author,
            'posts' => $posts,
            'personSchema' => $this->personSchema($author),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function personSchema(User $author): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $author->name,
            'url' => $author->authorUrl(),
            'image' => media_url($author->photo),
            'jobTitle' => $author->headline,
            'description' => $author->bio,
            'sameAs' => array_values(array_filter([$author->website_url, $author->linkedin_url, $author->x_url])) ?: null,
        ]);
    }
}
