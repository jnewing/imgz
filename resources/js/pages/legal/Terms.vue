<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LegalPage from '@/components/legal/LegalPage.vue';
import { aup, privacy } from '@/routes';
</script>

<template>
    <LegalPage title="Terms of Service" updated="July 23, 2026">
        <p>
            These Terms of Service ("Terms") govern your access to and use of
            imgz (the "Service"), a free image hosting and sharing service
            operated at imgz.net. By uploading, sharing, or otherwise using the
            Service, you agree to these Terms. If you do not agree, do not use
            the Service.
        </p>

        <section>
            <h2>1. The service</h2>
            <p>
                imgz lets you upload images and share them by link, with or
                without an account. Uploaded images are <strong>unlisted</strong>
                but accessible to anyone who has the link; they are not published
                to any public gallery. You may set an optional expiry so an
                upload is removed automatically after a chosen period.
            </p>
        </section>

        <section>
            <h2>2. Eligibility</h2>
            <p>
                You must be at least 13 years old to use the Service, and old
                enough to form a binding contract in your jurisdiction. If you
                use the Service on behalf of an organization, you represent that
                you are authorized to accept these Terms for it.
            </p>
        </section>

        <section>
            <h2>3. Accounts</h2>
            <p>
                You may use the Service anonymously or create an account. If you
                create an account, you are responsible for your credentials and
                for all activity that occurs under it, including any API tokens
                you generate. Keep your credentials and tokens secret, and notify
                us promptly of any unauthorized use.
            </p>
        </section>

        <section>
            <h2>4. Your content</h2>
            <p>
                You retain all ownership rights in the images you upload ("Your
                Content"). You are solely responsible for Your Content and for
                ensuring you have the rights necessary to upload and share it.
            </p>
            <p>
                You grant imgz a worldwide, non-exclusive, royalty-free license
                to host, store, reproduce, resize, and transmit Your Content
                solely to operate and provide the Service, for example to
                store your uploads, generate thumbnails, and serve images to the
                people you share links with. This license ends when the content
                is deleted, except for copies retained transiently in caches or
                backups for a reasonable period.
            </p>
        </section>

        <section>
            <h2>5. Acceptable use</h2>
            <p>
                Your use of the Service must comply with our
                <Link :href="aup()">Acceptable Use Policy</Link>, which is
                incorporated into these Terms. Among other things, you may not
                upload illegal content or use the Service to harm others.
            </p>
        </section>

        <section>
            <h2>6. Content removal and suspension</h2>
            <p>
                We may remove any content and suspend or terminate access at any
                time, with or without notice, if we believe it violates these
                Terms or the Acceptable Use Policy, is unlawful, or exposes us or
                others to liability. When content is removed for a policy
                violation, we may record a fingerprint of the file to prevent it
                from being re-uploaded.
            </p>
        </section>

        <section>
            <h2>7. Copyright and reporting</h2>
            <p>
                If you believe content on the Service infringes your copyright or
                otherwise violates the law or these Terms, report it using the
                report option on the image's page, or contact us at
                <a href="mailto:abuse@imgz.net">abuse@imgz.net</a>. We respond to
                valid notices, including takedown requests, and may remove
                content and terminate repeat infringers.
            </p>
        </section>

        <section>
            <h2>8. Service availability</h2>
            <p>
                The Service is provided free of charge on an "as is" and "as
                available" basis. We do not guarantee that it will be
                uninterrupted, secure, or error-free, that any image will be
                stored indefinitely, or that data will never be lost. Keep your
                own copies of anything important; do not rely on imgz as your
                only copy.
            </p>
        </section>

        <section>
            <h2>9. Rate limits</h2>
            <p>
                We apply rate limits, storage limits, and daily quotas to keep
                the Service healthy. You may not attempt to circumvent these
                limits or place an unreasonable load on our infrastructure.
            </p>
        </section>

        <section>
            <h2>10. Disclaimers and limitation of liability</h2>
            <p>
                To the fullest extent permitted by law, imgz and its operators
                disclaim all warranties, express or implied, and will not be
                liable for any indirect, incidental, special, consequential, or
                exemplary damages, or for any loss of data, arising out of or
                relating to your use of the Service. Our total liability for any
                claim relating to the Service will not exceed one hundred U.S.
                dollars ($100).
            </p>
        </section>

        <section>
            <h2>11. Indemnification</h2>
            <p>
                You agree to indemnify and hold harmless imgz and its operators
                from any claims, damages, and expenses (including reasonable
                legal fees) arising from Your Content or your violation of these
                Terms or applicable law.
            </p>
        </section>

        <section>
            <h2>12. Changes to these Terms</h2>
            <p>
                We may update these Terms from time to time. When we do, we will
                revise the "Last updated" date above. Your continued use of the
                Service after changes take effect constitutes acceptance of the
                revised Terms.
            </p>
        </section>

        <section>
            <h2>13. Privacy</h2>
            <p>
                Our <Link :href="privacy()">Privacy Policy</Link> explains what
                information we collect, how we use it, and the choices you have.
                By using the Service, you agree to the practices described there.
            </p>
        </section>

        <section>
            <h2>14. Governing law</h2>
            <p>
                These Terms are governed by the laws of the Province of Ontario
                and the federal laws of Canada applicable therein, without regard
                to conflict-of-laws principles. You agree to the exclusive
                jurisdiction of the courts located in Ontario, Canada for any
                dispute arising out of or relating to these Terms or the Service,
                except where prohibited by applicable law.
            </p>
        </section>

        <section>
            <h2>15. Contact</h2>
            <p>
                Questions about these Terms? Contact us at
                <a href="mailto:support@imgz.net">support@imgz.net</a>.
            </p>
        </section>
    </LegalPage>
</template>
