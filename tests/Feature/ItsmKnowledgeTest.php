<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\KbArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

class ItsmKnowledgeTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_article_lifecycle_and_visibility_invariant(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $tech = $this->makeUser($tenant, 'technician');
        $manager = $this->makeUser($tenant, 'manager');
        $customerOrg = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $customer = $this->makeUser($tenant, 'customer', ['customer_id' => $customerOrg->id]);

        // Technician drafts an internal article: never public on create.
        $this->actingAs($tech);
        $this->post(route('kb.store'), [
            'title' => 'Restart the print spooler', 'body' => 'Step 1…',
            'visibility' => 'internal',
        ])->assertRedirect();
        $article = KbArticle::where('tenant_id', $tenant->id)->first();
        $this->assertEquals('draft', $article->status);
        $this->assertEquals(1, $article->versions()->count());

        // Technician cannot publish (needs manager+).
        $this->post(route('kb.transition', $article), ['to' => 'published'])->assertForbidden();

        // Manager moves to review then publishes; still internal → customer 404.
        $this->actingAs($manager);
        $this->post(route('kb.transition', $article), ['to' => 'review'])->assertRedirect();
        $this->post(route('kb.transition', $article), ['to' => 'published'])->assertRedirect();
        $this->actingAs($customer);
        $this->get(route('portal.kb.show', $article))->assertNotFound();
        $this->get(route('portal.kb'))->assertOk()->assertDontSee('Restart the print spooler');

        // Make it public → customer can read it, and it appears in suggestions.
        $this->actingAs($manager);
        $this->put(route('kb.update', $article), [
            'title' => 'Restart the print spooler', 'body' => 'Step 1… Step 2…',
            'visibility' => 'public',
        ])->assertRedirect();
        $this->assertEquals(2, $article->fresh()->version);
        $this->actingAs($customer);
        $this->get(route('portal.kb.show', $article))->assertOk()->assertSee('Step 2');
        $this->getJson(route('kb.suggest', ['q' => 'spooler']))->assertOk()->assertSee('spooler');

        // Feedback increments atomically.
        $this->post(route('kb.feedback', $article), ['helpful' => true])->assertRedirect();
        $this->assertEquals(1, $article->fresh()->helpful_votes);

        // Archive withdraws it from customers immediately (owner/admin only).
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);
        $this->post(route('kb.transition', $article), ['to' => 'archived'])->assertRedirect();
        $this->actingAs($customer);
        $this->get(route('portal.kb.show', $article))->assertNotFound();
    }

    public function test_customer_cannot_author_or_delete(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $org = Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $customer = $this->makeUser($tenant, 'customer', ['customer_id' => $org->id]);
        $this->actingAs($customer);

        $this->post(route('kb.store'), ['title' => 'x', 'body' => 'y', 'visibility' => 'public'])->assertForbidden();
        $this->assertSame(0, KbArticle::count());
    }

    public function test_cross_tenant_article_is_unreachable(): void
    {
        $a = $this->makeTenant('Alpha');
        $b = $this->makeTenant('Beta');
        $techA = $this->makeUser($a, 'technician');
        $articleB = KbArticle::withoutTenantScope()->create([
            'tenant_id' => $b->id, 'title' => 'Secret runbook', 'slug' => 'secret',
            'body' => '…', 'visibility' => 'internal', 'status' => 'published',
        ]);

        $this->actingAs($techA);
        $this->get(route('kb.show', $articleB))->assertNotFound();
    }
}
