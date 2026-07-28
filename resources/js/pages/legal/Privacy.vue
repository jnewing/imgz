<script setup lang="ts">
import LegalPage from '@/components/legal/LegalPage.vue';
</script>

<template>
    <LegalPage title="Privacy Policy" updated="July 23, 2026">
        <p>
            This Privacy Policy explains what information imgz ("we", "us")
            collects when you use imgz.net (the "Service"), how we use it, and
            the choices you have. imgz is operated and hosted in Ontario,
            Canada, and we handle personal information in accordance with
            Canada's Personal Information Protection and Electronic Documents Act
            (PIPEDA).
        </p>

        <section>
            <h2>The short version</h2>
            <p>
                We collect as little as possible. We do not keep server request
                logs, we do not run analytics or advertising trackers, we do not
                use tracking cookies, and we do not sell your information. We
                only store what we need to provide the Service and keep it free
                of abuse.
            </p>
        </section>

        <section>
            <h2>Information we store</h2>
            <ul>
                <li>
                    <strong>Account information.</strong> If you create an
                    account, we store your name, email address, and a securely
                    hashed password. Any API tokens you create are stored in
                    hashed form.
                </li>
                <li>
                    <strong>Uploaded images.</strong> We store the images you
                    upload and basic technical metadata about them (dimensions,
                    file size, format, upload time, view count, and any expiry
                    you set). Images are unlisted but accessible to anyone who
                    has the link.
                </li>
                <li>
                    <strong>Abuse reports.</strong> When you report an image, we
                    store your IP address alongside the report so we can prevent
                    duplicate and abusive reports.
                </li>
                <li>
                    <strong>Moderation fingerprints.</strong> When content is
                    removed for a policy violation, we store a cryptographic
                    checksum (a one-way fingerprint) of the file so it cannot be
                    re-uploaded. This is derived from the file, not from you.
                </li>
            </ul>
        </section>

        <section>
            <h2>Information we process but do not log</h2>
            <p>
                To protect the Service, we process IP addresses in real time to
                enforce rate limits and daily upload quotas. These are used
                transiently for abuse prevention and are not written to logs or
                retained beyond the short window needed to apply the limits.
            </p>
        </section>

        <section>
            <h2>Cookies</h2>
            <p>
                We use only the functional cookies required to run the Service:
                a session cookie and a security (CSRF) cookie, plus your saved
                light/dark appearance preference. We do not use advertising or
                analytics cookies, and we do not track you across other sites.
            </p>
        </section>

        <section>
            <h2>How we use information</h2>
            <p>We use the information above only to:</p>
            <ul>
                <li>Provide, operate, and maintain the Service.</li>
                <li>
                    Prevent abuse and enforce our Terms of Service and Acceptable
                    Use Policy.
                </li>
                <li>Respond to your requests and support inquiries.</li>
                <li>Comply with legal obligations.</li>
            </ul>
        </section>

        <section>
            <h2>Sharing and disclosure</h2>
            <p>
                We do not sell or rent your personal information. We may disclose
                information if we reasonably believe it is required to comply with
                a law, regulation, legal process, or enforceable governmental
                request, or to detect, prevent, or address abuse, security, or
                technical issues, or to protect the rights, property, or safety
                of imgz, our users, or the public.
            </p>
        </section>

        <section>
            <h2>Retention</h2>
            <p>
                Images are kept until you delete them or they reach the expiry
                you set. Account information is kept for as long as your account
                exists. Abuse reports and moderation fingerprints are retained as
                long as necessary for abuse prevention. When you delete content
                or your account, we remove the associated data, except for copies
                that may persist transiently in caches or backups for a
                reasonable period.
            </p>
        </section>

        <section>
            <h2>Your choices and rights</h2>
            <p>Under PIPEDA, you may:</p>
            <ul>
                <li>
                    Delete an image at any time using the delete link you receive
                    when you upload it, or from your dashboard if you have an
                    account.
                </li>
                <li>
                    Request access to, correction of, or deletion of the personal
                    information associated with your account.
                </li>
                <li>Delete your account, which removes your account data.</li>
                <li>
                    Ask questions or file a complaint about how we handle your
                    information.
                </li>
            </ul>
            <p>
                To exercise any of these, email
                <a href="mailto:support@imgz.net">support@imgz.net</a>.
            </p>
        </section>

        <section>
            <h2>Children</h2>
            <p>
                The Service is not directed to children under 13, and we do not
                knowingly collect personal information from them. If you believe a
                child has provided us personal information, contact us and we will
                delete it.
            </p>
        </section>

        <section>
            <h2>Location of data</h2>
            <p>
                The Service is hosted in Ontario, Canada, and your information is
                stored and processed there. By using the Service, you understand
                that your information will be handled as described in this policy.
            </p>
        </section>

        <section>
            <h2>Changes and contact</h2>
            <p>
                We may update this policy from time to time; when we do, we will
                revise the "Last updated" date above. For any privacy question or
                request, contact us at
                <a href="mailto:support@imgz.net">support@imgz.net</a>.
            </p>
        </section>
    </LegalPage>
</template>
