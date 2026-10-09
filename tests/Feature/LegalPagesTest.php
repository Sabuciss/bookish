<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_policy_is_public_and_links_to_terms(): void
    {
        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSeeText('Privātuma politika')
            ->assertSee(route('terms'));
    }

    public function test_terms_are_public_and_link_to_privacy_policy(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSeeText('Lietošanas noteikumi')
            ->assertSee(route('privacy-policy'));
    }
}