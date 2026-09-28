<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    // tests kabhi asli Abstract credit nahi lagate na DNS pe depend karte hain - har email deliverable,
    // jab tak test khud kuch aur fake na kare
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.abstract.email_validation_key' => 'test-key']);

        Http::fake([
            'emailvalidation.abstractapi.com/*' => Http::response([
                'deliverability' => 'DELIVERABLE',
                'is_valid_format' => ['value' => true],
                'is_disposable_email' => ['value' => false],
            ]),
        ]);
    }
}
