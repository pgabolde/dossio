<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

const props = defineProps<{
    paginated: Paginated<unknown>;
    label: { one: string; many: string };
}>();

const navigation = computed(() => [
    { label: 'Précédent', url: props.paginated.links.prev },
    { label: 'Suivant', url: props.paginated.links.next },
]);

const totalLabel = computed(() =>
    props.paginated.meta.total > 1 ? props.label.many : props.label.one,
);
</script>

<template>
    <div class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
            {{ paginated.meta.total }} {{ totalLabel }} - Page
            {{ paginated.meta.current_page }} / {{ paginated.meta.last_page }}
        </p>
        <div class="flex gap-2">
            <template v-for="item in navigation" :key="item.label">
                <Button v-if="item.url" variant="outline" as-child>
                    <Link :href="item.url">{{ item.label }}</Link>
                </Button>
                <Button v-else variant="outline" disabled>{{
                    item.label
                }}</Button>
            </template>
        </div>
    </div>
</template>
