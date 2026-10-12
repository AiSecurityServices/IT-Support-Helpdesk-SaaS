<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

/** Public marketing website (configurable branding, SEO meta, sitemap). */
class MarketingController extends Controller
{
    public function home()
    {
        return view('marketing.home', ['plans' => Plan::where('is_active', true)->orderBy('price_monthly')->get()]);
    }

    public function features()
    {
        return view('marketing.features');
    }

    public function useCases()
    {
        return view('marketing.use-cases');
    }

    public function pricing()
    {
        return view('marketing.pricing', ['plans' => Plan::where('is_active', true)->orderBy('price_monthly')->get()]);
    }

    public function about()
    {
        return view('marketing.about');
    }

    public function contact()
    {
        return view('marketing.contact');
    }

    public function contactStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255', 'message' => 'required|string|max:5000',
        ]);
        // Log-based delivery by default; SMTP when configured (see .env.example).
        \Illuminate\Support\Facades\Mail::raw(
            "Contact form submission from {$data['name']} ({$data['email']}, ".($data['company'] ?? 'no company')."):\n\n{$data['message']}",
            fn ($m) => $m->to(config('branding.support_email'))->subject('Website contact: '.$data['name'])
        );

        return back()->with('success', 'Thanks — we’ll be in touch shortly.');
    }

    public function faq()
    {
        return view('marketing.faq');
    }

    public function security()
    {
        return view('marketing.security');
    }

    public function resources()
    {
        return view('marketing.resources', ['plans' => Plan::where('is_active', true)->orderBy('price_monthly')->get()]);
    }

    public function privacy()
    {
        return view('marketing.privacy');
    }

    public function terms()
    {
        return view('marketing.terms');
    }

    public function sitemap()
    {
        $pages = ['', 'features', 'use-cases', 'pricing', 'about', 'contact', 'faq', 'security', 'resources', 'privacy', 'terms', 'login', 'register'];

        return response()->view('marketing.sitemap', compact('pages'))->header('Content-Type', 'application/xml');
    }
}
