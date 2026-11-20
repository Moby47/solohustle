<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A standalone page. Drafts are visible only to the admin, as a preview. */
class ShowPage extends Controller
{
    public function __invoke(Request $request, string $slug): View
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $isAdmin = (bool) $request->user()?->isAdmin();

        abort_if(! $page->is_published && ! $isAdmin, 404);

        return view('pages.show', [
            'page' => $page,
            'isAdmin' => $isAdmin,
        ]);
    }
}
