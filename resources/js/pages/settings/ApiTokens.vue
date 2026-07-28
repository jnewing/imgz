<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Check, Copy, KeyRound, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useClipboard } from '@/composables/useClipboard';
import { index, store, destroy } from '@/routes/api-tokens';

interface Token {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    created_at: string;
}

const props = defineProps<{
    tokens: Token[];
    abilities: { value: string; label: string }[];
    newToken: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'API tokens', href: index() }],
    },
});

const form = useForm({
    name: '',
    abilities: [] as string[],
});

function toggleAbility(value: string, checked: boolean | 'indeterminate'): void {
    if (checked) {
        form.abilities = [...form.abilities, value];
    } else {
        form.abilities = form.abilities.filter((a) => a !== value);
    }
}

function createToken(): void {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function revoke(token: Token): void {
    router.delete(destroy.url(token.id), { preserveScroll: true });
}

const { copy } = useClipboard();
const copied = ref(false);

function copyToken(): void {
    if (!props.newToken) {
return;
}

    copy(props.newToken, 'Token copied');
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 1500);
}

const uploadUrl = computed(
    () =>
        `${typeof window !== 'undefined' ? window.location.origin : ''}/api/v1/images`,
);
</script>

<template>
    <Head title="API tokens" />

    <h1 class="sr-only">API tokens</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="API tokens"
            description="Create tokens to upload and manage images programmatically."
        />

        <!-- Newly created token, shown once -->
        <Card v-if="newToken" class="border-primary/40 bg-primary/5">
            <CardContent class="space-y-3 p-4">
                <p class="text-sm font-medium">
                    Your new token. Copy it now, it won't be shown again.
                </p>
                <div class="relative">
                    <Input
                        :model-value="newToken"
                        readonly
                        class="pr-10 font-mono text-xs"
                        @focus="(e: FocusEvent) => (e.target as HTMLInputElement).select()"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        class="absolute top-1/2 right-1 -translate-y-1/2"
                        aria-label="Copy token"
                        @click="copyToken"
                    >
                        <Check v-if="copied" class="size-4 text-primary" />
                        <Copy v-else class="size-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Create token -->
        <form class="space-y-4" @submit.prevent="createToken">
            <div class="space-y-2">
                <Label for="token-name">Token name</Label>
                <Input
                    id="token-name"
                    v-model="form.name"
                    placeholder="e.g. My upload script"
                    :aria-invalid="Boolean(form.errors.name)"
                />
                <p v-if="form.errors.name" class="text-sm text-destructive">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="space-y-2">
                <Label>Permissions</Label>
                <div class="flex flex-col gap-2">
                    <label
                        v-for="ability in abilities"
                        :key="ability.value"
                        class="flex cursor-pointer items-center gap-2 text-sm"
                    >
                        <Checkbox
                            :model-value="form.abilities.includes(ability.value)"
                            @update:model-value="
                                (v) => toggleAbility(ability.value, v)
                            "
                        />
                        <span>{{ ability.label }}</span>
                        <code class="text-xs text-muted-foreground">{{
                            ability.value
                        }}</code>
                    </label>
                </div>
                <p v-if="form.errors.abilities" class="text-sm text-destructive">
                    {{ form.errors.abilities }}
                </p>
            </div>

            <Button type="submit" :disabled="form.processing">
                <KeyRound class="size-4" />
                Create token
            </Button>
        </form>

        <!-- Existing tokens -->
        <div v-if="tokens.length" class="space-y-2">
            <Label>Active tokens</Label>
            <Card v-for="token in tokens" :key="token.id">
                <CardContent class="flex items-center justify-between gap-4 p-4">
                    <div class="min-w-0 space-y-1">
                        <p class="truncate text-sm font-medium">
                            {{ token.name }}
                        </p>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="ability in token.abilities"
                                :key="ability"
                                variant="secondary"
                                class="font-mono text-[10px]"
                                >{{ ability }}</Badge
                            >
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Created {{ token.created_at }} ·
                            {{
                                token.last_used_at
                                    ? `last used ${token.last_used_at}`
                                    : 'never used'
                            }}
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="text-muted-foreground hover:text-destructive"
                        aria-label="Revoke token"
                        @click="revoke(token)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- Usage -->
        <Card>
            <CardContent class="space-y-2 p-4">
                <p class="text-sm font-medium">Uploading with the API</p>
                <pre
                    class="overflow-x-auto rounded-md bg-muted/40 p-3 font-mono text-xs text-foreground"
                >curl -X POST {{ uploadUrl }} \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -F "image=@photo.jpg"</pre>
                <p class="text-xs text-muted-foreground">
                    The <code>image</code> field accepts a file or a base64
                    string. The response includes the direct link and a delete
                    URL.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
