<?php

namespace App\Http\Controllers;

use App\Models\KbArticle;
use App\Models\KbArticleVersion;
use App\Models\KbCategory;
use App\Models\KbFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Knowledge management. Safety invariant: an article is customer-visible
 * ONLY when status=published AND visibility=public. Every query path
 * (staff list, portal, search, suggestions, API-style partials) enforces it.
 */
class KnowledgeBaseController extends Controller
{
    protected function tenantId(): int
    {
        return (int) currentTenantId();
    }

    // ---------- staff ----------

    public function index(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);
        $query = KbArticle::with(['category', 'author'])
            ->where('tenant_id', $this->tenantId())->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('body', 'like', $term)->orWhere('tags', 'like', $term));
        }

        return view('kb.index', [
            'articles' => $query->paginate(15)->withQueryString(),
            'categories' => KbCategory::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);

        return view('kb.create', $this->formData());
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);
        $data = $this->validateArticle($request);

        return DB::transaction(function () use ($request, $data) {
            $article = KbArticle::create($data + [
                'tenant_id' => $this->tenantId(),
                'author_id' => $request->user()->id,
                'status' => 'draft', // never published on create; explicit review step required
            ]);
            $this->snapshot($article, $request->user()->id);
            audit('kb.created', $article, ['title' => $article->title]);

            return redirect()->route('kb.show', $article)->with('success', 'Draft saved. Submit for review to publish.');
        });
    }

    public function show(Request $request, KbArticle $article)
    {
        $this->authorizeArticle($request, $article, 'view');
        $article->load(['category', 'author', 'versions', 'feedback']);

        // Related: same category, published, same visibility-or-broader.
        $related = KbArticle::where('tenant_id', $this->tenantId())
            ->where('id', '!=', $article->id)
            ->where('status', 'published')
            ->when($request->user()->isCustomer(), fn ($q) => $q->where('visibility', 'public'))
            ->when($article->kb_category_id, fn ($q) => $q->where('kb_category_id', $article->kb_category_id))
            ->limit(5)->get();

        return view('kb.show', compact('article', 'related'));
    }

    public function edit(Request $request, KbArticle $article)
    {
        $this->authorizeArticle($request, $article, 'edit');

        return view('kb.edit', ['article' => $article] + $this->formData());
    }

    public function update(Request $request, KbArticle $article)
    {
        $this->authorizeArticle($request, $article, 'edit');
        $data = $this->validateArticle($request);

        return DB::transaction(function () use ($request, $article, $data) {
            $article->fill($data);
            // Editing a published article bumps the version and keeps history.
            $article->version = $article->version + 1;
            $article->save();
            $this->snapshot($article, $request->user()->id);
            audit('kb.updated', $article, ['version' => $article->version]);

            return redirect()->route('kb.show', $article)->with('success', "Saved as version {$article->version}.");
        });
    }

    /** Move through draft → review → published → archived with permission checks. */
    public function transition(Request $request, KbArticle $article)
    {
        $this->authorizeArticle($request, $article, 'edit');
        $request->validate(['to' => 'required|in:draft,review,published,archived']);
        $to = $request->input('to');

        // Publishing requires review permission; deletion-like archive needs admin.
        if ($to === 'published') {
            abort_unless($request->user()->hasRole('owner', 'admin', 'manager'), 403, 'Review permission required to publish.');
            $article->last_reviewed_at = now();
        }
        if ($to === 'archived') {
            abort_unless($request->user()->hasRole('owner', 'admin'), 403);
        }
        // Only published+public is ever customer-visible; unpublish on archive.
        $from = $article->status;
        $article->status = $to;
        $article->save();
        audit('kb.transition', $article, ['from' => $from, 'to' => $to]);

        return back()->with('success', "Article moved to {$to}.");
    }

    public function destroy(Request $request, KbArticle $article)
    {
        $this->authorizeArticle($request, $article, 'delete');
        $article->delete();
        audit('kb.deleted', $article, ['title' => $article->title]);

        return redirect()->route('kb.index')->with('success', 'Article archived (soft-deleted).');
    }

    public function storeCategory(Request $request)
    {
        abort_unless($request->user()->canManage('configuration'), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string|max:1000']);
        KbCategory::firstOrCreate(['tenant_id' => $this->tenantId(), 'name' => $data['name']], $data);

        return back()->with('success', 'Category added.');
    }

    // ---------- customer & suggestions ----------

    /** Customer-facing library: published + public only. */
    public function portalIndex(Request $request)
    {
        abort_unless($request->user()->isCustomer(), 403);
        $query = KbArticle::with('category')->where('tenant_id', $this->tenantId())
            ->where('status', 'published')->where('visibility', 'public')->latest();
        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('body', 'like', $term));
        }

        return view('portal.kb', ['articles' => $query->paginate(12)->withQueryString()]);
    }

    public function portalShow(Request $request, KbArticle $article)
    {
        abort_unless($request->user()->isCustomer(), 403);
        abort_unless((int) $article->tenant_id === $this->tenantId() && $article->isPubliclyVisible(), 404);
        $article->load(['category']);

        return view('portal.kb-show', compact('article'));
    }

    /** Suggestions during ticket creation (JSON + Blade partial compatible). */
    public function suggest(Request $request)
    {
        $request->validate(['q' => 'required|string|min:3|max:200']);
        $term = '%'.$request->input('q').'%';
        $articles = KbArticle::where('tenant_id', $this->tenantId())
            ->where('status', 'published')
            ->when($request->user()->isCustomer(), fn ($q) => $q->where('visibility', 'public'))
            ->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('body', 'like', $term)->orWhere('tags', 'like', $term))
            ->limit(5)->get(['id', 'title', 'slug']);

        if ($request->wantsJson()) {
            return response()->json($articles);
        }

        return view('kb.partials.suggestions', compact('articles'));
    }

    public function feedback(Request $request, KbArticle $article)
    {
        // Only on articles the user is allowed to see.
        $this->authorizeArticle($request, $article, 'view');
        $request->validate(['helpful' => 'required|boolean', 'comment' => 'nullable|string|max:1000']);

        return DB::transaction(function () use ($request, $article) {
            KbFeedback::create([
                'kb_article_id' => $article->id, 'user_id' => $request->user()->id,
                'helpful' => $request->boolean('helpful'), 'comment' => $request->input('comment'),
            ]);
            // Atomic counters.
            $article->increment($request->boolean('helpful') ? 'helpful_votes' : 'not_helpful_votes');

            return back()->with('success', 'Thanks for your feedback.');
        });
    }

    // ---------- internals ----------

    protected function authorizeArticle(Request $request, KbArticle $article, string $ability): void
    {
        abort_unless((int) $article->tenant_id === $this->tenantId(), 404);
        $user = $request->user();

        if ($ability === 'view') {
            if ($user->isStaff()) {
                return;
            }
            abort_unless($article->isPubliclyVisible(), 404);
            abort_unless((int) $user->customer_id > 0, 403);

            return;
        }
        // edit/delete are staff-only; customers can never author platform docs.
        abort_unless($user->isStaff(), 403);
        if ($ability === 'delete') {
            abort_unless($user->hasRole('owner', 'admin'), 403);
        }
    }

    protected function validateArticle(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:200000',
            'kb_category_id' => 'nullable|exists:kb_categories,id',
            'visibility' => 'required|in:public,internal',
            'tags' => 'nullable|string|max:500',
        ]);
        if (! empty($data['kb_category_id'])) {
            abort_unless(KbCategory::where('tenant_id', $this->tenantId())->where('id', $data['kb_category_id'])->exists(), 422);
        }

        return $data;
    }

    protected function snapshot(KbArticle $article, ?int $userId): void
    {
        KbArticleVersion::create([
            'kb_article_id' => $article->id, 'version' => $article->version,
            'title' => $article->title, 'body' => $article->body, 'edited_by' => $userId,
        ]);
    }

    protected function formData(): array
    {
        return ['categories' => KbCategory::where('tenant_id', $this->tenantId())->orderBy('name')->get()];
    }
}
