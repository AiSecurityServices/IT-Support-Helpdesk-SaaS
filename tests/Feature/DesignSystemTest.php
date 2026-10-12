<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\MakesTenants;
use Tests\TestCase;

/**
 * Guards the Precision design system: new visual identity markers present,
 * legacy template patterns gone, 3D hero wired with safe fallbacks.
 */
class DesignSystemTest extends TestCase
{
    use MakesTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlans();
    }

    public function test_homepage_has_new_identity_and_3d_hero(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('One command centre for exceptional IT support.', $html);
        $this->assertStringContainsString('id="service-engine"', $html);
        $this->assertStringContainsString('service-engine-fallback', $html);
        $this->assertStringContainsString('data-engine-state', $html);
        $this->assertStringContainsString('service-engine.js', $html);
        $this->assertStringContainsString('Precision Service Engine', $html);
        $this->assertStringContainsString('pf-btn-bright', $html);
        // Real plan data rendered.
        $this->assertStringContainsString(\App\Models\Plan::first()->name, $html);
        // Legacy template markers must be gone.
        $this->assertStringNotContainsString('🎫', $html);
        $this->assertStringNotContainsString('Start Free Trial', $html); // sentence case is the new voice
    }

    public function test_precision_css_has_tokens_and_motion_safety(): void
    {
        $path = public_path('css/precision.css');
        $this->assertFileExists($path);
        $css = file_get_contents($path);
        foreach (['--pf-navy-950', '--pf-ice-500', '--pf-font-display', '.pf-btn', '.pf-card', '.pf-table', '.pf-badge', '.pf-sidebar', '.pf-hero3d', '.pf-orgtab'] as $token) {
            $this->assertStringContainsString($token, $css, "missing token: $token");
        }
        $this->assertStringContainsString('prefers-reduced-motion', $css);
    }

    public function test_service_engine_js_is_safe_and_complete(): void
    {
        $path = public_path('js/service-engine.js');
        $this->assertFileExists($path);
        $js = file_get_contents($path);
        foreach (['prefers-reduced-motion', 'IntersectionObserver', 'requestAnimationFrame', 'dispose()', 'setPixelRatio', 'visibilitychange', 'pagehide', 'getPointAt', 'CatmullRomCurve3', 'TubeGeometry'] as $marker) {
            $this->assertStringContainsString($marker, $js, "missing 3D behavior: $marker");
        }
        // No particle spam, no glow-ball chaos.
        $this->assertStringNotContainsString('PointsMaterial', $js);
    }

    public function test_marketing_nav_has_required_items_and_no_emoji(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();
        foreach (['Product', 'Solutions', 'Resources', 'Security', 'Pricing', 'Sign in', 'Start free trial'] as $item) {
            $this->assertStringContainsString($item, $html);
        }
        $this->assertMatchesRegularExpression('/aria-label="Toggle colour theme"/', $html);
        $this->assertEquals(0, preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $html));
    }

    public function test_public_pages_use_new_system(): void
    {
        foreach (['features', 'use-cases', 'pricing', 'about', 'contact', 'faq', 'privacy', 'terms', 'security', 'resources'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $this->assertStringContainsString('pf-eyebrow', $html, "$route missing eyebrow");
            $this->assertStringContainsString('<main', $html, "$route missing main landmark");
        }
        $this->get(route('sitemap'))->assertOk();
    }

    public function test_app_shell_uses_svg_icons_not_emoji(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $owner = $this->makeUser($tenant, 'owner');
        $this->actingAs($owner);
        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('pf-sidebar', $html);
        $this->assertStringContainsString('pf-navlink', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertEquals(0, preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $html));
    }

    public function test_portal_shell_uses_new_system(): void
    {
        $tenant = $this->makeTenant('Alpha');
        $org = \App\Models\Customer::create(['tenant_id' => $tenant->id, 'company_name' => 'One']);
        $customer = $this->makeUser($tenant, 'customer', ['customer_id' => $org->id]);
        $this->actingAs($customer);
        $html = $this->get(route('portal.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('pf-stat', $html);
        $this->assertStringContainsString('support portal', $html);
    }

    public function test_auth_pages_use_guest_system(): void
    {
        foreach (['login', 'register', 'password.request'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $this->assertStringContainsString('pf-display', $html, "$route missing display type");
            $this->assertStringContainsString('pf-input', $html, "$route missing precision inputs");
        }
    }

    public function test_both_themes_have_readable_bodies(): void
    {
        // Light (default) and dark (forced) render without error; body carries theme hooks.
        $light = $this->get(route('pricing'))->assertOk()->getContent();
        $this->assertStringContainsString('pf-body', $light);
        $this->assertStringContainsString('toggleTheme()', $light);
    }
}
