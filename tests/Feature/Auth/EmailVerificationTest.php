<?php

namespace Tests\Feature\Auth;

use App\Mail\Transport\GmailApiTransport;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_gmail_api_transport_sends_base64url_encoded_message_through_its_sender(): void
    {
        $encodedMessage = null;
        $transport = new GmailApiTransport('client-id', 'client-secret', 'refresh-token', function (string $raw) use (&$encodedMessage): string {
            $encodedMessage = $raw;

            return 'gmail-message-id';
        });
        $email = (new Email())
            ->from('bookish@example.com')
            ->to('reader@example.com')
            ->subject('Verify Email Address')
            ->text('Please verify your email.');

        $sentMessage = $transport->send($email);
        $base64Message = strtr($encodedMessage, '-_', '+/');
        $decodedMessage = base64_decode($base64Message.str_repeat('=', (4 - strlen($base64Message) % 4) % 4), true);

        $this->assertIsString($decodedMessage);
        $this->assertStringContainsString('Subject: Verify Email Address', $decodedMessage);
        $this->assertSame('gmail-message-id', $sentMessage->getMessageId());
        $this->assertSame('gmail-api', (string) $transport);
    }

    public function test_gmail_api_transport_requires_oauth_credentials(): void
    {
        $transport = new GmailApiTransport(null, null, null);
        $email = (new Email())
            ->from('bookish@example.com')
            ->to('reader@example.com')
            ->subject('Verify Email Address')
            ->text('Please verify your email.');

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Gmail API OAuth credentials are not configured.');

        $transport->send($email);
    }

    public function test_gmail_api_transport_is_registered_with_laravel(): void
    {
        $this->assertTrue(class_exists(\Google\Client::class));
        $this->assertTrue(class_exists(\Google\Service\Gmail::class));
        $this->assertTrue(class_exists(\Google\Service\Gmail\Message::class));

        config([
            'services.gmail_api.client_id' => 'client-id',
            'services.gmail_api.client_secret' => 'client-secret',
            'services.gmail_api.refresh_token' => 'refresh-token',
        ]);

        $transport = app('mail.manager')->createSymfonyTransport(['transport' => 'gmail-api']);

        $this->assertInstanceOf(GmailApiTransport::class, $transport);
    }

    public function test_unverified_users_can_use_the_app_and_are_told_to_verify_in_their_profile(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('reading-shelf.show'));

        $this->get(route('reading-shelf.show'))->assertOk();
        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSeeText('Tavs e-pasts vēl nav verificēts.');
    }

    public function test_unverified_admin_still_cannot_open_admin_dashboard(): void
    {
        $admin = User::factory()->unverified()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_user_can_request_another_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_failed_resend_shows_a_message_instead_of_a_server_error(): void
    {
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new TransportException('SMTP unavailable.'));
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-email-failed');
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
