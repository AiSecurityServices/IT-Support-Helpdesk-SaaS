<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\MakesTenants;
use Tests\TestCase;

/**
 * Automated accessibility smoke checks (WCAG 2.2 AA target).
 * These catch structural regressions; they do NOT replace the manual
 * assistive-technology pass recorded in ACCESSIBILITY_REPORT.md.
 */
class AccessibilityTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    /** @dataProvider guestPages */
    public function test_guest_pages_have_accessible_structure(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();
        $this->assertAccessibleDocument($html, $url);
    }

    public function guestPages(): array
    {
        return [
            'home' => ['/'], 'pricing' => ['/pricing'], 'features' => ['/features'],
            'login' => ['/login'], 'register' => ['/register'],
            'forgot password' => ['/forgot-password'], 'faq' => ['/faq'],
        ];
    }

    /** @dataProvider authedPages */
    public function test_authed_pages_have_accessible_structure(string $route): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);
        $html = $this->get(route($route))->assertOk()->getContent();
        $this->assertAccessibleDocument($html, $route);
    }

    public function authedPages(): array
    {
        return [
            'dashboard' => ['dashboard'], 'tickets' => ['tickets.index'],
            'ticket create' => ['tickets.create'], 'customers' => ['customers.index'],
            'kb' => ['kb.index'], 'automation' => ['automation.index'],
            'settings' => ['settings.index'], 'profile' => ['profile.edit'],
        ];
    }

    public function test_portal_pages_have_accessible_structure(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $org = \App\Models\Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $customer = $this->makeUser($tenant, 'customer', ['customer_id' => $org->id]);
        $this->actingAs($customer);
        foreach (['portal.dashboard', 'portal.tickets', 'portal.services', 'portal.kb'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $this->assertAccessibleDocument($html, $route);
        }
    }

    protected function assertAccessibleDocument(string $html, string $context): void
    {
        $crawler = new Crawler($html);

        // Language + title + main heading + landmark.
        $this->assertNotEmpty($crawler->filter('html')->attr('lang'), "$context: missing html lang");
        $this->assertNotEmpty(trim($crawler->filter('title')->text('')), "$context: empty <title>");
        $this->assertGreaterThanOrEqual(1, $crawler->filter('h1')->count(), "$context: no h1");
        $this->assertGreaterThanOrEqual(1, $crawler->filter('main, [role="main"]')->count(), "$context: no main landmark");

        // Skip link for keyboard users.
        $this->assertGreaterThanOrEqual(1, $crawler->filter('a[href="#main-content"]')->count(), "$context: no skip link");

        // Form controls must be labelled (label[for], aria-label(ledby), or wrapped).
        $crawler->filter('input, select, textarea')->each(function (Crawler $node) use ($context) {
            $type = strtolower($node->attr('type') ?? 'text');
            if (in_array($type, ['hidden', 'submit', 'button'], true)) {
                return;
            }
            $labelled = (bool) ($node->attr('aria-label') || $node->attr('aria-labelledby'))
                || $this->hasLabelFor($node)
                || $node->ancestors()->filter('label')->count() > 0;
            $this->assertTrue($labelled, "$context: unlabelled <{$node->nodeName()}> control");
        });

        // Images need alt text; buttons/links need accessible names.
        $crawler->filter('img')->each(function (Crawler $node) use ($context) {
            $this->assertNotNull($node->attr('alt'), "$context: img without alt");
        });
        $crawler->filter('button')->each(function (Crawler $node) use ($context) {
            $this->assertNotEmpty(trim($node->text('')) ?: $node->attr('aria-label'), "$context: button without name");
        });

        // Data tables need scoped headers.
        $crawler->filter('table')->each(function (Crawler $node) use ($context) {
            $this->assertGreaterThanOrEqual(1, $node->filter('th')->count(), "$context: table without th");
        });
    }

    protected function hasLabelFor(Crawler $node): bool
    {
        $doc = $node->getNode(0)->ownerDocument;
        $id = $node->attr('id');
        if (! $id) {
            return false;
        }
        $xpath = new \DOMXPath($doc);

        return $xpath->query("//label[@for='{$id}']")->length > 0;
    }
}
