<?php

namespace Tests\Feature\Auth;

use App\Mail\Transport\ResendApiTransport;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Client\Request as HttpRequest;
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

    public function test_resend_transport_sends_mail_through_the_https_api(): void
    {
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'resend-message-id'], 200),
        ]);
        $transport = new ResendApiTransport('re_test_api_key');
        $email = (new Email())
            ->from('Bookish <noreply@bookish.lv>')
            ->to('reader@example.com')
            ->subject('Verify Email Address')
            ->text('Please verify your email.');

        $sentMessage = $transport->send($email);

        Http::assertSent(function (HttpRequest $request): bool {
            $data = $request->data();

            return $request->url() === 'https://api.resend.com/emails'
                && $request->hasHeader('Authorization')
                && str_contains($data['from'] ?? '', 'noreply@bookish.lv')
                && ($data['to'] ?? null) === ['reader@example.com']
                && ($data['subject'] ?? null) === 'Verify Email Address'
                && ($data['text'] ?? null) === 'Please verify your email.';
        });
        $this->assertSame('resend-message-id', $sentMessage->getMessageId());
        $this->assertSame('resend-api', (string) $transport);
    }

    public function test_resend_transport_requires_an_api_key(): void
    {
        $transport = new ResendApiTransport(null);
        $email = (new Email())
            ->from('bookish@example.com')
            ->to('reader@example.com')
            ->subject('Verify Email Address')
            ->text('Please verify your email.');

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Resend API key is not configured.');

        $transport->send($email);
    }

    public function test_resend_api_transport_is_registered_with_laravel(): void
    {
        config(['services.resend.key' => 're_test_api_key']);

        $transport = app('mail.manager')->createSymfonyTransport(['transport' => 'resend-api']);

        $this->assertInstanceOf(ResendApiTransport::class, $transport);
    }

    public function test_unverified_users_are_redirected_to_verify_before_using_the_app(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->get(route('reading-shelf.show'))
            ->assertRedirect(route('verification.notice'));
        $this->get(route('profile.edit'))
            ->assertRedirect(route('verification.notice'));
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
