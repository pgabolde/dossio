<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { destroy, edit, index } from '@/routes/clients';
import type { Client } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

defineProps<{
    client: Client;
    can: { update: boolean; delete: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clients',
                href: index(),
            },
            {
                // title: `Client : ${props.client.name}`,
                title: 'Client',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Client : ${client.name}`" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">{{ client.name }}</h1>
            <div class="flex gap-2">
                <Button v-if="can.update" variant="outline" as-child>
                    <Link :href="edit(client.id)">Modifier</Link>
                </Button>

                <Dialog v-if="can.delete">
                    <DialogTrigger as-child>
                        <Button variant="destructive">Supprimer</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle
                                >Supprimer {{ client.name }} ?</DialogTitle
                            >
                            <DialogDescription>
                                Le client sera définitivement supprimé. Cette
                                action est irréversible.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button variant="outline">Annuler</Button>
                            </DialogClose>
                            <Form
                                v-bind="destroy.form(client.id)"
                                v-slot="{ processing }"
                            >
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                >
                                    Supprimer
                                </Button>
                            </Form>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
        <dl
            class="grid grid-cols-[auto_1fr] gap-x-8 gap-y-3 rounded-md border p-4"
        >
            <dt class="text-muted-foreground">Email</dt>
            <dd>{{ client.email ?? '—' }}</dd>

            <dt class="text-muted-foreground">Téléphone</dt>
            <dd>{{ client.phone ?? '—' }}</dd>

            <dt class="text-muted-foreground">SIRET</dt>
            <dd>{{ client.siret ?? '—' }}</dd>
        </dl>
        <div>
            <h2 class="text-lg font-semibold">Documents</h2>
            <p
                class="rounded-md border border-dashed p-8 text-center text-muted-foreground"
            >
                Aucun document pour l'instant
            </p>
        </div>
    </div>
</template>
