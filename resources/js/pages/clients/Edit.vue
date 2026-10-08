<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show, update } from '@/routes/clients';
import type { Client } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';

defineProps<{ client: Client }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clients', href: index() }],
    },
});
</script>

<template>
    <Head :title="`Modifier : ${client.name}`" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-semibold">Modifier {{ client.name }}</h1>

        <Form
            v-bind="update.form(client.id)"
            v-slot="{ errors, processing }"
            class="flex max-w-xl flex-col gap-6"
        >
            <div class="grid gap-2">
                <Label for="name">Nom</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="client.name"
                    required
                />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    :default-value="client.email ?? ''"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Téléphone</Label>
                <Input
                    id="phone"
                    name="phone"
                    type="tel"
                    :default-value="client.phone ?? ''"
                />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="siret">SIRET</Label>
                <Input
                    id="siret"
                    name="siret"
                    inputmode="numeric"
                    maxlength="14"
                    :default-value="client.siret ?? ''"
                />
                <InputError :message="errors.siret" />
            </div>
            <div class="flex gap-2">
                <Button type="submit" :disabled="processing"
                    >Enregistrer</Button
                >
                <Button variant="outline" as-child>
                    <Link :href="show(client.id)">Annuler</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
