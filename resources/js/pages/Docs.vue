<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import { computed } from 'vue';
import CodeBlock from '@/components/docs/CodeBlock.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { index as apiTokens } from '@/routes/api-tokens';

interface Ability {
    value: string;
    label: string;
}

const props = defineProps<{
    api: {
        base_url: string;
        filehost_url: string;
        upload_url: string;
        max_files: number;
        max_size_kb: number;
        accept: string[];
        abilities: Ability[];
        throttle: {
            user: { per_minute: number; per_day: number };
        };
    };
}>();

const baseUrl = computed(() => props.api.base_url);
const filehostUrl = computed(() => props.api.filehost_url);
const uploadHost = computed(() =>
    props.api.upload_url.replace(/\/api\/upload$/, ''),
);
const origin = computed(() => props.api.base_url.replace(/\/api\/v1$/, ''));
const maxSizeMb = computed(() => Math.round(props.api.max_size_kb / 1024));

const extensions = computed(() =>
    props.api.accept
        .map((mime) => mime.replace('image/', '').toUpperCase())
        .join(', '),
);

const sections = [
    { id: 'introduction', label: 'Introduction' },
    { id: 'authentication', label: 'Authentication' },
    { id: 'rate-limits', label: 'Rate limits' },
    { id: 'upload', label: 'Upload an image' },
    { id: 'list', label: 'List images' },
    { id: 'retrieve', label: 'Retrieve an image' },
    { id: 'delete', label: 'Delete an image' },
    { id: 'image-object', label: 'The image object' },
    { id: 'errors', label: 'Errors' },
    { id: 'irc-filehost', label: 'Share from IRC' },
];

const uploadMultipart = computed(
    () => `curl -X POST ${baseUrl.value}/images \\
  -H "Authorization: Bearer $IMGZ_TOKEN" \\
  -H "Accept: application/json" \\
  -F "image=@/path/to/photo.jpg"`,
);

const uploadBase64 = computed(
    () => `curl -X POST ${baseUrl.value}/images \\
  -H "Authorization: Bearer $IMGZ_TOKEN" \\
  -H "Accept: application/json" \\
  --data-urlencode "image=$(base64 -w0 photo.jpg)"`,
);

const uploadResponse = computed(
    () => `{
  "data": {
    "public_id": "aB3dE5gH7j",
    "url": "${origin.value}/i/aB3dE5gH7j",
    "direct_url": "${origin.value}/i/aB3dE5gH7j.jpg",
    "thumbnail_url": "${origin.value}/i/aB3dE5gH7j_thumb.webp",
    "width": 1920,
    "height": 1080,
    "size": 482913,
    "human_size": "471.6 KB",
    "mime_type": "image/jpeg",
    "media_type": "image",
    "title": null,
    "views": 0,
    "created_at": "2026-07-23T14:12:05+00:00",
    "expires_at": null,
    "delete_url": "${origin.value}/i/aB3dE5gH7j/delete/f1e2d3c4b5"
  }
}`,
);

const listRequest = computed(
    () => `curl "${baseUrl.value}/images?per_page=30" \\
  -H "Authorization: Bearer $IMGZ_TOKEN" \\
  -H "Accept: application/json"`,
);

const listResponse = computed(
    () => `{
  "data": [
    { "public_id": "aB3dE5gH7j", "url": "${origin.value}/i/aB3dE5gH7j", ... },
    { "public_id": "kL9mN2pQ4r", "url": "${origin.value}/i/kL9mN2pQ4r", ... }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 30, "total": 42 }
}`,
);

const showRequest = computed(
    () => `curl ${baseUrl.value}/images/aB3dE5gH7j \\
  -H "Authorization: Bearer $IMGZ_TOKEN" \\
  -H "Accept: application/json"`,
);

const deleteRequest = computed(
    () => `curl -X DELETE ${baseUrl.value}/images/aB3dE5gH7j \\
  -H "Authorization: Bearer $IMGZ_TOKEN"`,
);

const filehostIsupport = computed(
    () => `draft/FILEHOST=${filehostUrl.value}`,
);

const filehostRaw = computed(
    () => `curl -X POST --data-binary @cat.png \\
  -H "Content-Type: image/png" \\
  ${filehostUrl.value}
# => ${origin.value}/i/aB3dE5gH7j.png`,
);

const filehostAuthed = computed(
    () => `curl -X POST --data-binary @cat.png \\
  -H "Content-Type: image/png" \\
  -H "Authorization: Bearer $IMGZ_TOKEN" \\
  ${filehostUrl.value}`,
);

const halloyConfig = computed(
    () => `[servers.<name>.filehost]
override_url = "${filehostUrl.value}"

# Attribute uploads to your account: put an images:write
# token in the password (the username can be anything).
[servers.<name>.filehost.credentials.plain]
username = "me"
password = "<your imgz API token>"`,
);

const imageFields: { field: string; type: string; description: string }[] = [
    { field: 'public_id', type: 'string', description: 'The image identifier used in every URL and endpoint.' },
    { field: 'url', type: 'string', description: 'Link to the hosted viewer page.' },
    { field: 'direct_url', type: 'string', description: 'Direct link to the original image file (hotlinkable).' },
    { field: 'thumbnail_url', type: 'string', description: 'Direct link to the generated thumbnail.' },
    { field: 'width', type: 'integer|null', description: 'Pixel width of the original image.' },
    { field: 'height', type: 'integer|null', description: 'Pixel height of the original image.' },
    { field: 'size', type: 'integer', description: 'File size of the original, in bytes.' },
    { field: 'human_size', type: 'string', description: 'Human-readable file size, e.g. "471.6 KB".' },
    { field: 'mime_type', type: 'string', description: 'The stored MIME type, e.g. "image/jpeg".' },
    { field: 'media_type', type: 'string', description: 'Coarse media class, e.g. "image".' },
    { field: 'title', type: 'string|null', description: 'Optional title.' },
    { field: 'views', type: 'integer', description: 'Number of views on the viewer page.' },
    { field: 'created_at', type: 'string', description: 'ISO 8601 upload timestamp.' },
    { field: 'expires_at', type: 'string|null', description: 'ISO 8601 auto-expiry timestamp, or null to keep forever.' },
    { field: 'delete_url', type: 'string', description: 'Capability link to delete the image. Only returned on upload.' },
];

const errorRows: { status: string; meaning: string }[] = [
    { status: '401 Unauthorized', meaning: 'Missing or invalid token.' },
    { status: '403 Forbidden', meaning: 'The token lacks the required ability for this endpoint.' },
    { status: '404 Not Found', meaning: 'The image does not exist or is not owned by your account.' },
    { status: '422 Unprocessable Entity', meaning: 'Validation failed: unsupported type, oversized file, or malformed base64.' },
    { status: '429 Too Many Requests', meaning: 'Rate limit or daily quota exceeded. Retry after the window resets.' },
];

function methodClass(method: string): string {
    switch (method) {
        case 'POST':
            return 'bg-primary/15 text-primary';
        case 'DELETE':
            return 'bg-destructive/15 text-destructive';
        default:
            return 'bg-muted text-muted-foreground';
    }
}
</script>

<template>
    <Head title="API documentation" />

    <PublicLayout>
        <div class="mx-auto w-full max-w-5xl px-6 pt-16 pb-12 sm:pt-20">
            <header class="mb-10">
                <p
                    class="text-sm font-medium tracking-wide text-primary uppercase"
                >
                    Developers
                </p>
                <h1
                    class="mt-1 text-3xl font-semibold tracking-tight sm:text-4xl"
                >
                    API reference
                </h1>
                <p class="mt-3 max-w-2xl text-muted-foreground">
                    A simple REST API for uploading and managing images. Use it
                    from your own apps, scripts, or whatever else you're building.
                </p>
            </header>

            <div class="gap-10 lg:grid lg:grid-cols-[200px_minmax(0,1fr)]">
                <!-- Table of contents -->
                <aside class="hidden lg:block">
                    <nav class="sticky top-8 space-y-1 text-sm">
                        <a
                            v-for="section in sections"
                            :key="section.id"
                            :href="`#${section.id}`"
                            class="block rounded-md px-3 py-1.5 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                        >
                            {{ section.label }}
                        </a>
                    </nav>
                </aside>

                <!-- Content -->
                <main
                    class="min-w-0 space-y-14 [&_h2]:scroll-mt-8 [&_h3]:scroll-mt-8"
                >
                    <!-- Introduction -->
                    <section id="introduction" class="space-y-4">
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Introduction
                        </h2>
                        <p class="text-muted-foreground">
                            The API is versioned and rooted at the base URL
                            below. All responses are JSON; send
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >Accept: application/json</code
                            >
                            so validation errors come back as JSON too.
                        </p>
                        <CodeBlock label="Base URL" :code="baseUrl" />
                        <p class="text-sm text-muted-foreground">
                            Uploads accept {{ extensions }} up to
                            {{ maxSizeMb }} MB each.
                        </p>
                    </section>

                    <!-- Authentication -->
                    <section id="authentication" class="space-y-4">
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Authentication
                        </h2>
                        <p class="text-muted-foreground">
                            Authenticate with a personal access token, sent as a
                            bearer token on every request. Create and manage
                            tokens under your account settings. Each token is
                            scoped to one or more abilities.
                        </p>
                        <CodeBlock
                            code="Authorization: Bearer <YOUR_TOKEN>"
                        />

                        <div class="overflow-x-auto rounded-lg border border-border">
                            <table class="w-full text-left text-sm">
                                <thead
                                    class="border-b border-border bg-muted/40 text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-4 py-2 font-medium">
                                            Ability
                                        </th>
                                        <th class="px-4 py-2 font-medium">
                                            Grants
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="ability in api.abilities"
                                        :key="ability.value"
                                        class="border-b border-border/60 last:border-0"
                                    >
                                        <td class="px-4 py-2">
                                            <code
                                                class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs"
                                                >{{ ability.value }}</code
                                            >
                                        </td>
                                        <td
                                            class="px-4 py-2 text-muted-foreground"
                                        >
                                            {{ ability.label }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <Button as-child variant="outline">
                            <Link :href="apiTokens()">
                                <KeyRound class="size-4" />
                                Manage API tokens
                            </Link>
                        </Button>
                    </section>

                    <!-- Rate limits -->
                    <section id="rate-limits" class="space-y-4">
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Rate limits
                        </h2>
                        <p class="text-muted-foreground">
                            Uploads are rate limited per account:
                            <strong class="text-foreground"
                                >{{ api.throttle.user.per_minute }} per
                                minute</strong
                            >
                            with a daily quota of
                            <strong class="text-foreground"
                                >{{ api.throttle.user.per_day }} uploads</strong
                            >. A single request uploads one image. Exceeding a
                            limit returns
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >429 Too Many Requests</code
                            >; read endpoints are not quota-limited.
                        </p>
                    </section>

                    <!-- Upload -->
                    <section id="upload" class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge
                                class="font-mono"
                                :class="methodClass('POST')"
                                >POST</Badge
                            >
                            <code class="font-mono text-sm">/images</code>
                        </div>
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Upload an image
                        </h2>
                        <p class="text-muted-foreground">
                            Send a multipart
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >image</code
                            >
                            file, or an
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >image</code
                            >
                            field containing base64 data (with or without a
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >data:</code
                            >
                            URI prefix). Requires the
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >images:write</code
                            >
                            ability. Returns
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >201 Created</code
                            >
                            with the image object, including a one-time
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >delete_url</code
                            >.
                        </p>
                        <CodeBlock
                            label="Multipart upload"
                            :code="uploadMultipart"
                        />
                        <CodeBlock
                            label="Base64 upload"
                            :code="uploadBase64"
                        />
                        <h3 class="pt-2 text-sm font-semibold text-muted-foreground">
                            Response
                        </h3>
                        <CodeBlock
                            label="201 Created"
                            :code="uploadResponse"
                        />
                    </section>

                    <!-- List -->
                    <section id="list" class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge
                                class="font-mono"
                                :class="methodClass('GET')"
                                >GET</Badge
                            >
                            <code class="font-mono text-sm">/images</code>
                        </div>
                        <h2 class="text-2xl font-semibold tracking-tight">
                            List images
                        </h2>
                        <p class="text-muted-foreground">
                            Returns your uploads, most recent first, paginated.
                            Use
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >per_page</code
                            >
                            (default 30, max 100) to control page size. Requires
                            the
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >images:read</code
                            >
                            ability.
                        </p>
                        <CodeBlock :code="listRequest" />
                        <CodeBlock label="200 OK" :code="listResponse" />
                    </section>

                    <!-- Retrieve -->
                    <section id="retrieve" class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge
                                class="font-mono"
                                :class="methodClass('GET')"
                                >GET</Badge
                            >
                            <code class="font-mono text-sm"
                                >/images/{public_id}</code
                            >
                        </div>
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Retrieve an image
                        </h2>
                        <p class="text-muted-foreground">
                            Fetch a single image you own by its
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >public_id</code
                            >. Requires the
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >images:read</code
                            >
                            ability. Returns
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >404</code
                            >
                            if it does not exist or belongs to another account.
                        </p>
                        <CodeBlock :code="showRequest" />
                    </section>

                    <!-- Delete -->
                    <section id="delete" class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge
                                class="font-mono"
                                :class="methodClass('DELETE')"
                                >DELETE</Badge
                            >
                            <code class="font-mono text-sm"
                                >/images/{public_id}</code
                            >
                        </div>
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Delete an image
                        </h2>
                        <p class="text-muted-foreground">
                            Permanently deletes one of your images. Requires the
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >images:delete</code
                            >
                            ability. Returns
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >204 No Content</code
                            >
                            on success.
                        </p>
                        <CodeBlock :code="deleteRequest" />
                    </section>

                    <!-- Image object -->
                    <section id="image-object" class="space-y-4">
                        <h2 class="text-2xl font-semibold tracking-tight">
                            The image object
                        </h2>
                        <p class="text-muted-foreground">
                            Every endpoint returns image objects under a
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >data</code
                            >
                            key with the following fields.
                        </p>
                        <div class="overflow-x-auto rounded-lg border border-border">
                            <table class="w-full text-left text-sm">
                                <thead
                                    class="border-b border-border bg-muted/40 text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-4 py-2 font-medium">
                                            Field
                                        </th>
                                        <th class="px-4 py-2 font-medium">
                                            Type
                                        </th>
                                        <th class="px-4 py-2 font-medium">
                                            Description
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in imageFields"
                                        :key="row.field"
                                        class="border-b border-border/60 align-top last:border-0"
                                    >
                                        <td class="px-4 py-2">
                                            <code
                                                class="font-mono text-xs whitespace-nowrap"
                                                >{{ row.field }}</code
                                            >
                                        </td>
                                        <td
                                            class="px-4 py-2 font-mono text-xs whitespace-nowrap text-muted-foreground"
                                        >
                                            {{ row.type }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-muted-foreground"
                                        >
                                            {{ row.description }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Errors -->
                    <section id="errors" class="space-y-4">
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Errors
                        </h2>
                        <p class="text-muted-foreground">
                            The API uses conventional HTTP status codes.
                            Failures return a JSON body with a
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >message</code
                            >
                            (and
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >errors</code
                            >
                            for validation failures).
                        </p>
                        <div class="overflow-x-auto rounded-lg border border-border">
                            <table class="w-full text-left text-sm">
                                <thead
                                    class="border-b border-border bg-muted/40 text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-4 py-2 font-medium">
                                            Status
                                        </th>
                                        <th class="px-4 py-2 font-medium">
                                            Meaning
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in errorRows"
                                        :key="row.status"
                                        class="border-b border-border/60 last:border-0"
                                    >
                                        <td
                                            class="px-4 py-2 font-mono text-xs whitespace-nowrap"
                                        >
                                            {{ row.status }}
                                        </td>
                                        <td
                                            class="px-4 py-2 text-muted-foreground"
                                        >
                                            {{ row.meaning }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- IRC / FILEHOST -->
                    <section id="irc-filehost" class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge
                                class="font-mono"
                                :class="methodClass('POST')"
                                >POST</Badge
                            >
                            <code class="font-mono text-sm">/filehost</code>
                        </div>
                        <h2 class="text-2xl font-semibold tracking-tight">
                            Share from IRC
                        </h2>
                        <p class="text-muted-foreground">
                            imgz implements the IRCv3
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >draft/FILEHOST</code
                            >
                            upload contract, so any FILEHOST-capable IRC client
                            (such as Halloy) can upload straight to imgz and drop
                            the link into a channel. You do not need to run an IRC
                            server: an operator advertises this endpoint to
                            clients with an
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >ISUPPORT</code
                            >
                            token.
                        </p>
                        <CodeBlock
                            label="ISUPPORT token (set by the IRC server)"
                            :code="filehostIsupport"
                        />
                        <p class="text-muted-foreground">
                            The client
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >POST</code
                            >s the raw file bytes and receives
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >201 Created</code
                            >
                            with the hosted URL in the
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >Location</code
                            >
                            header (and in the plain-text body). Uploads accept
                            {{ extensions }} up to {{ maxSizeMb }} MB.
                        </p>
                        <CodeBlock label="Upload a file" :code="filehostRaw" />
                        <p class="text-muted-foreground">
                            Anonymous uploads work when this instance allows guest
                            uploads. To tie uploads to your account (and get the
                            higher account rate limit), send an
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >images:write</code
                            >
                            token as a bearer token, for example via your client's
                            filehost credentials setting.
                        </p>
                        <CodeBlock
                            label="Attribute uploads to your account"
                            :code="filehostAuthed"
                        />
                        <p class="text-muted-foreground">
                            Clients that authenticate a FILEHOST upload with SASL
                            PLAIN send the credentials as HTTP Basic. imgz reads
                            an
                            <code class="rounded bg-muted px-1 py-0.5 text-sm"
                                >images:write</code
                            >
                            token from the Basic password (or username), so in
                            <a
                                href="https://halloy.chat/guides/filehost"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium text-primary underline-offset-4 hover:underline"
                                >Halloy</a
                            >
                            you can attribute uploads to your account with a
                            standalone filehost credential:
                        </p>
                        <CodeBlock
                            label="Halloy config"
                            :code="halloyConfig"
                        />

                        <h3
                            class="pt-4 text-lg font-semibold tracking-tight"
                        >
                            Clients without FILEHOST (Lurker)
                        </h3>
                        <p class="text-muted-foreground">
                            Some clients don't speak FILEHOST but can upload to a
                            self-hosted
                            <a
                                href="https://github.com/diced/zipline"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium text-primary underline-offset-4 hover:underline"
                                >Zipline</a
                            >
                            instance. imgz is Zipline-compatible, so in
                            <a
                                href="https://github.com/amiantos/lurker"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium text-primary underline-offset-4 hover:underline"
                                >Lurker</a
                            >
                            (Settings, then Uploaders, then Add an uploader) pick
                            <strong class="text-foreground">Zipline</strong> and
                            enter:
                        </p>
                        <div
                            class="overflow-x-auto rounded-lg border border-border"
                        >
                            <table class="w-full text-left text-sm">
                                <tbody>
                                    <tr class="border-b border-border/60">
                                        <td
                                            class="px-4 py-2 font-medium whitespace-nowrap"
                                        >
                                            Zipline URL
                                        </td>
                                        <td class="px-4 py-2">
                                            <code
                                                class="font-mono text-xs"
                                                >{{ uploadHost }}</code
                                            >
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            class="px-4 py-2 font-medium whitespace-nowrap"
                                        >
                                            Zipline token
                                        </td>
                                        <td
                                            class="px-4 py-2 text-muted-foreground"
                                        >
                                            An imgz API token. Grant
                                            <code
                                                class="rounded bg-muted px-1 py-0.5 text-xs"
                                                >images:write</code
                                            >
                                            to upload, and also
                                            <code
                                                class="rounded bg-muted px-1 py-0.5 text-xs"
                                                >images:delete</code
                                            >
                                            if you want Lurker's delete button to
                                            remove files from imgz too.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </PublicLayout>
</template>
