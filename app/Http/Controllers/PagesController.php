<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PagesController extends Controller
{
    public function about(): View
    {
        return view('about');
    }

    public function contact(): View
    {
        // office ka marker hamesha same hai, model ki zaroorat nahi
        $officeMapMarkers = [[
            'lat' => config('marketlink.office_location.latitude'),
            'lng' => config('marketlink.office_location.longitude'),
            'name' => 'MarketLink office',
            'details' => config('marketlink.office_location.address'),
        ]];

        // FAQ ke Buyer / Farmer toggle ki static lists (contact.blade.php)
        $buyerFaqs = [
            [
                'question' => 'How do I reserve produce ahead of time?',
                'answer' => "Browse what's in stock on the Markets page, add what you want, and choose a pickup slot at checkout - no account needed to browse, just to reserve.",
            ],
            [
                'question' => 'Do I pay online or at the stall?',
                'answer' => "You pay the farmer directly at their stall when you collect your order - MarketLink doesn't process payments.",
            ],
            [
                'question' => 'What if an item runs out before my pickup time?',
                'answer' => 'Farmers only list what they actually have, and your reservation sets your items aside - so what you ordered is kept for you, not sold to someone else.',
            ],
            [
                'question' => 'Can I change or cancel my order?',
                'answer' => "Yes, from your dashboard up until the farmer's cutoff time for that pickup slot.",
            ],
            [
                'question' => "What if I can't make my pickup slot?",
                'answer' => "Contact the farmer's stall directly using the details on your order, or reach out to us and we'll help you sort it out.",
            ],
        ];

        $farmerFaqs = [
            [
                'question' => 'How do I list my stall on MarketLink?',
                'answer' => 'Tap "Register as a farmer," tell us about your stall and what you sell, and our admin team reviews and approves it before it goes live.',
            ],
            [
                'question' => 'Is there a fee to join?',
                'answer' => 'Joining and listing your stall is free; we only ask a small commission if a paid plan is introduced later.',
            ],
            [
                'question' => 'How do pre-orders help me?',
                'answer' => 'You see reservations ahead of market day, so you know exactly what to pack - no more guessing or unsold stock.',
            ],
            [
                'question' => 'How do I get paid?',
                'answer' => 'Customers pay you directly at your stall when they collect their order, exactly like a normal market day.',
            ],
            [
                'question' => 'How do I update what\'s in stock each week?',
                'answer' => 'From your farmer dashboard, update your product list and quantities any time before the market - customers only see what\'s currently available.',
            ],
        ];

        return view('contact', compact('officeMapMarkers', 'buyerFaqs', 'farmerFaqs'));
    }
}
