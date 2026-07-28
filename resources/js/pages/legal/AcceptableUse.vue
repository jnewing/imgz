<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LegalPage from '@/components/legal/LegalPage.vue';
import { terms } from '@/routes';
</script>

<template>
    <LegalPage title="Acceptable Use Policy" updated="July 23, 2026">
        <p>
            This Acceptable Use Policy ("AUP") describes what you may not do on
            imgz. It is part of our
            <Link :href="terms()">Terms of Service</Link>. We may update it at
            any time. Violating this policy can result in immediate removal of
            content, blocking of your uploads, termination of your account, and,
            where appropriate, reports to law enforcement.
        </p>

        <section>
            <h2>Prohibited content</h2>
            <p>You may not upload, store, or share content that:</p>
            <ul>
                <li>
                    Is unlawful, or that depicts, promotes, or facilitates
                    illegal activity.
                </li>
                <li>
                    <strong>
                        Sexually exploits or endangers minors in any way.
                    </strong>
                    Child sexual abuse material is strictly prohibited and will
                    be removed and reported to the National Center for Missing &
                    Exploited Children (NCMEC) and/or relevant authorities.
                </li>
                <li>
                    Is non-consensual intimate imagery, or otherwise sexual
                    content shared without the consent of the people depicted.
                </li>
                <li>
                    Depicts, incites, or glorifies violence, gore, terrorism, or
                    self-harm.
                </li>
                <li>
                    Harasses, threatens, defames, or targets a person or group,
                    or promotes hatred or discrimination based on protected
                    characteristics.
                </li>
                <li>
                    Infringes anyone's intellectual property, privacy, or
                    publicity rights, or discloses another person's private
                    information without permission.
                </li>
                <li>
                    Contains malware, phishing, or content designed to deceive,
                    defraud, or compromise the security of others.
                </li>
            </ul>
        </section>

        <section>
            <h2>Prohibited conduct</h2>
            <p>You may not:</p>
            <ul>
                <li>
                    Use the Service to send spam, or to host images used in
                    unsolicited or bulk messaging.
                </li>
                <li>
                    Attempt to bypass rate limits, storage limits, or daily
                    quotas, or place an unreasonable load on our infrastructure,
                    including through abusive hotlinking.
                </li>
                <li>
                    Access the Service with automated tools in a way that
                    disrupts it, or scrape or enumerate content you were not
                    given links to.
                </li>
                <li>
                    Probe, scan, or test the vulnerability of the Service, or
                    breach or circumvent any security or authentication measure.
                </li>
                <li>
                    Resell, sublicense, or commercially exploit the Service
                    without our permission.
                </li>
            </ul>
        </section>

        <section>
            <h2>Enforcement</h2>
            <p>
                We may remove content, block a file from being re-uploaded,
                throttle or suspend access, and terminate accounts to enforce
                this policy at our discretion and, where we judge it necessary,
                without prior notice. We cooperate with law enforcement where
                required by law.
            </p>
        </section>

        <section>
            <h2>Reporting abuse</h2>
            <p>
                To report content that violates this policy, use the report
                option on the image's page, or email
                <a href="mailto:abuse@imgz.net">abuse@imgz.net</a>. Please
                include the image link and a brief description of the problem.
            </p>
        </section>
    </LegalPage>
</template>
