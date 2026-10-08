<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Client, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { create, index, show } from '@/routes/clients';
import Button from '@/components/ui/button/Button.vue';
import AppPagination from '@/components/AppPagination.vue';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { watchDebounced } from '@vueuse/core';

const props = defineProps<{
    clients: Paginated<Client>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clients',
                href: index(),
            },
        ],
    },
});

const search = ref(props.filters.search);

watchDebounced(
    search,
    (value) => {
        router.get(
            index().url,
            { search: value || undefined },
            { preserveState: true, replace: true },
        );
    },
    { debounce: 300 },
);
</script>

<template>
    <Head title="Clients" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Clients</h1>
            <Button as-child>
                <Link :href="create()">Nouveau client</Link>
            </Button>
        </div>
        <Input
            v-model="search"
            placeholder="Rechercher par nom, email ou SIRET…"
            class="max-w-sm"
        />
        <template v-if="clients.data.length > 0">
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nom</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Tél.</TableHead>
                            <TableHead>SIRET</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="client in clients.data"
                            :key="client.id"
                        >
                            <TableCell>
                                <Link
                                    :href="show(client.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ client.name }}
                                </Link>
                            </TableCell>

                            <TableCell>{{ client.email ?? '—' }}</TableCell>
                            <TableCell>{{ client.phone ?? '—' }}</TableCell>
                            <TableCell>{{ client.siret ?? '—' }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <AppPagination
                :paginated="clients"
                :label="{ one: 'client', many: 'clients' }"
            />
        </template>
        <p
            v-else
            class="rounded-md border border-dashed p-8 text-center text-muted-foreground"
        >
            {{
                filters.search
                    ? `Aucun client ne correspond à « ${filters.search} »`
                    : 'Aucun client pour l`instant'
            }}
        </p>
    </div>
</template>
