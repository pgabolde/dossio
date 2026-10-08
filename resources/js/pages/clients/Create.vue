<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { store } from '@/routes/clients';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { create, index } from '@/routes/clients';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clients',
                href: index(),
            },
            {
                title: 'Nouveau client',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Nouveau Client" />

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Nom</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Nom du client"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="Email du client"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Téléphone</Label>
                <Input
                    id="phone"
                    type="tel"
                    :tabindex="3"
                    autocomplete="phone"
                    name="phone"
                    placeholder="Téléphone du client"
                />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="email">SIRET</Label>
                <Input
                    id="siret"
                    type="text"
                    :tabindex="4"
                    autocomplete="siret"
                    name="siret"
                    placeholder="SIRET du client"
                    maxlength="14"
                    inputmode="numeric"
                    pattern="[0-9]{14}"
                />
                <InputError :message="errors.siret" />
            </div>
            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="processing"
                data-test="create-client-button"
            >
                <Spinner v-if="processing" />
                Créer le client
            </Button>
        </div>
    </Form>
</template>
