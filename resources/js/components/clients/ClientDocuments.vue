<script setup lang="ts">
import type { ClientDocument } from '@/types';
import { Form } from '@inertiajs/vue3';
import { store as documentStore } from '@/routes/clients/documents';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { download } from '@/routes/documents';

defineProps<{
    clientId: number;
    documents: ClientDocument[];
    canUpload: boolean;
}>();

const statusDisplay: Record<
    ClientDocument['status'],
    { label: string; variant: BadgeVariants['variant']}
> = {
    pending: { label: "En attente d'analyse", variant: 'secondary'},
    processing: { label: 'Analyse en cours', variant: 'outline'},
    ready: { label: 'Analysé', variant: 'default'},
    failed: { label: "Échec de l'analyse", variant: 'destructive'},
};

function formatSize(bytes: number): string {
    const format = (n: number) =>
        n.toLocaleString('fr-FR', { maximumFractionDigits: 1 });

    if (bytes < 1024 * 1024) {
        return `${format(bytes / 1024)} Ko`;
    }

    return `${format(bytes / (1024 * 1024))} Mo`;
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR');
}

</script>

<template>
    <section class="flex flex-col gap-4">
        <h2 class="text-lg font-semibold">Documents</h2>

        <Form
            v-if="canUpload"
            v-bind="documentStore.form(clientId)"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
            reset-on-success
        >
            <div class="grid gap-2">
                <Label for="file">Nouveau document</Label>
                <Input id="file" type="file" name="file" accept="application/pdf"/>
                <InputError :message="errors.file" />
            </div>
            <Button
                type="submit"
                class="mt-2 w-full"
                :disabled="processing"
                data-test="upload-document-button"
            >
                <Spinner v-if="processing" />
                Ajouter le document
            </Button>
        </Form>

        <Table v-if="documents.length > 0">
            <TableHeader>
                <TableRow>
                    <TableHead>Nom</TableHead>
                    <TableHead>Taille</TableHead>
                    <TableHead>Statut</TableHead>
                    <TableHead>Ajouté le</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="doc in documents" :key="doc.id">
                    <TableCell class="font-medium">
                        <a :href="download.url(doc.id)" class="hover:underline">
                            {{ doc.original_filename }}
                        </a>
                    </TableCell>
                    <TableCell>{{ formatSize(doc.size) }}</TableCell>
                    <TableCell>
                        <Badge :variant="statusDisplay[doc.status].variant">
                            {{ statusDisplay[doc.status].label }}
                        </Badge>
                    </TableCell>
                    <TableCell>{{ formatDate(doc.created_at) }}</TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <p
            v-else
            class="rounded-md border border-dashed p-8 text-center text-muted-foreground"
        >
            Aucun document pour l'instant
        </p>
    </section>
</template>
