<?php

namespace Modules\CORE\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public legal pages (Terms, Privacy, Refund). Static content rendered through
 * the public layout so visitors and subscribers can read the policies.
 */
class LegalController extends Controller
{
    public function terms(): Response
    {
        return Inertia::render('CORE::Legal/Page', [
            'title' => 'Terms of Service',
            'updated' => '13 August 2026',
            'sections' => $this->termsSections(),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('CORE::Legal/Page', [
            'title' => 'Privacy Policy',
            'updated' => '13 August 2026',
            'sections' => $this->privacySections(),
        ]);
    }

    public function refund(): Response
    {
        return Inertia::render('CORE::Legal/Page', [
            'title' => 'Refund Policy',
            'updated' => '13 August 2026',
            'sections' => $this->refundSections(),
        ]);
    }

    /**
     * @return array<int, array{heading: string, body: string}>
     */
    private function termsSections(): array
    {
        return [
            ['heading' => '1. Acceptance of Terms', 'body' => 'By accessing or using Hisabiya ("the Service"), you agree to be bound by these Terms of Service. If you are using the Service on behalf of a business, you represent that you have authority to bind that business.'],
            ['heading' => '2. Description of Service', 'body' => 'Hisabiya provides a modular SaaS platform for personal and business accounting, including expense tracking, budgets, savings goals, loans, and reporting. Additional modules may be added from time to time. Each module is a separate workspace under your account.'],
            ['heading' => '3. Accounts & Subscriptions', 'body' => 'You must provide accurate information when creating an account. Paid plans are billed on a recurring basis. You are responsible for keeping your login credentials secure and for all activity under your account. We may suspend or terminate access if you violate these terms.'],
            ['heading' => '4. Acceptable Use', 'body' => 'You agree not to misuse the Service, attempt to access another tenant\'s data, reverse engineer the platform, or use the Service for unlawful purposes. You are responsible for ensuring the data you enter does not infringe on third-party rights.'],
            ['heading' => '5. Data & Privacy', 'body' => 'You own the data you enter into the Service. Your use of the Service is also governed by our Privacy Policy. We process data only to provide and improve the Service.'],
            ['heading' => '6. Payments & Renewals', 'body' => 'Subscription fees are charged at the start of each billing period. Manual payment methods are activated once confirmed. Unpaid renewals may result in restricted or revoked access after a grace period. Refunds are handled per our Refund Policy.'],
            ['heading' => '7. Intellectual Property', 'body' => 'The Service, including its software, design, and content, is owned by Hisabiya and its licensors. You may not copy, modify, distribute, or resell the Service except as permitted by a written agreement.'],
            ['heading' => '8. Disclaimer of Warranties', 'body' => 'The Service is provided "as is" and "as available" without warranties of any kind. We do not guarantee that the Service will be uninterrupted, error-free, or that your accounting data will be free of errors. You are responsible for verifying your own records.'],
            ['heading' => '9. Limitation of Liability', 'body' => 'To the maximum extent permitted by law, Hisabiya shall not be liable for indirect, incidental, special, consequential, or punitive damages, or for any loss of profits, revenue, data, or business opportunities arising from your use of the Service.'],
            ['heading' => '10. Termination', 'body' => 'You may cancel your subscription at any time. We may suspend or terminate accounts that violate these terms. On termination, your access to the Service and your data may be removed. You may export your data before terminating.'],
            ['heading' => '11. Changes to Terms', 'body' => 'We may update these Terms from time to time. We will notify you of material changes. Continued use of the Service after changes take effect constitutes acceptance of the revised terms.'],
            ['heading' => '12. Governing Law', 'body' => 'These Terms are governed by the laws of the People\'s Republic of Bangladesh. Any disputes shall be subject to the exclusive jurisdiction of the courts of Bangladesh.'],
        ];
    }

    /**
     * @return array<int, array{heading: string, body: string}>
     */
    private function privacySections(): array
    {
        return [
            ['heading' => '1. Information We Collect', 'body' => 'We collect information you provide directly, such as your name, email address, phone number, company name, and billing details. We also collect data you enter into your accounting workspaces. We may automatically collect usage data such as IP address, browser type, and pages visited.'],
            ['heading' => '2. How We Use Your Information', 'body' => 'We use your information to provide and improve the Service, process transactions, send you important account and billing notifications, provide customer support, and ensure the security of the platform. We do not sell your personal data.'],
            ['heading' => '3. Tenant Data Isolation', 'body' => 'Your accounting data is stored in a multi-tenant architecture and is strictly isolated to your tenant. Your data is never accessible to other tenants.'],
            ['heading' => '4. Payments', 'body' => 'Payment transactions are processed by third-party payment providers (such as SSLCommerz and bKash) or verified manually by our team. We do not store full payment card details on our servers.'],
            ['heading' => '5. Data Storage & Security', 'body' => 'We store data on secured servers and use industry-standard safeguards including encryption and access controls. While we take reasonable measures to protect your data, no method of transmission over the internet is completely secure.'],
            ['heading' => '6. Data Retention', 'body' => 'We retain your data for as long as your account is active or as needed to provide the Service and comply with legal obligations. You may request deletion of your account and data.'],
            ['heading' => '7. Your Rights', 'body' => 'You may access, correct, export, or delete your personal data. You may opt out of non-essential communications. You can exercise these rights by contacting support.'],
            ['heading' => '8. Cookies & Local Storage', 'body' => 'We use cookies and local storage to maintain your session, remember preferences, and improve the experience. You can configure your browser to reject cookies, though some features may not function properly.'],
            ['heading' => '9. Third-Party Services', 'body' => 'We may use third-party services for email delivery, payment processing, and analytics. These providers process data under their own privacy policies.'],
            ['heading' => '10. Changes to This Policy', 'body' => 'We may update this Privacy Policy from time to time. We will notify you of material changes. Your continued use of the Service constitutes acceptance of the updated policy.'],
            ['heading' => '11. Contact', 'body' => 'If you have questions about this Privacy Policy or your data, please contact us through the support channels available on the platform.'],
        ];
    }

    /**
     * @return array<int, array{heading: string, body: string}>
     */
    private function refundSections(): array
    {
        return [
            ['heading' => '1. Overview', 'body' => 'This Refund Policy applies to paid subscriptions to Hisabiya. We aim to be fair and transparent about refunds.'],
            ['heading' => '2. Subscription Refunds', 'body' => 'If you are not satisfied with a paid subscription, you may request a refund within the first 7 days of a new subscription, provided no substantial use of the Service has occurred. Refunds are issued to the original payment method where possible.'],
            ['heading' => '3. Renewal Refunds', 'body' => 'Automatic or manual renewals may be refunded if requested within 7 days of the renewal charge and if the renewed period has not been used.'],
            ['heading' => '4. Manual Payments (bKash / Bank Transfer)', 'body' => 'For manual bKash or bank transfer payments, refunds are processed back to the same account you paid from. Please include your TRX ID or transaction reference when requesting a refund. Refunds may take up to 7-10 business days to appear.'],
            ['heading' => '5. How to Request a Refund', 'body' => 'To request a refund, contact our support team with your account email, subscription details, and the reason for the refund. We will review your request and respond within a reasonable timeframe.'],
            ['heading' => '6. Non-Refundable Cases', 'body' => 'Refunds are generally not issued for: charges older than 30 days, misuse of the Service, or cases where access was revoked due to a violation of our Terms of Service.'],
            ['heading' => '7. Contact', 'body' => 'For any refund questions, please reach out through the support channels on the platform. We\'re here to help.'],
        ];
    }
}
