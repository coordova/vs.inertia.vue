<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { type CharacterResource } from '@/types/global'; // Asumiendo que CharacterResource tiene fullname, nickname, picture_url, elo_rating, etc.

interface Props {
    character: CharacterResource;
    showElo?: boolean; // Mostrar o no el rating ELO
    showStats?: boolean; // Mostrar o no estadísticas adicionales (si están disponibles en CharacterResource)
    // Puedes añadir más props para personalizar la apariencia o comportamiento
}

const props = withDefaults(defineProps<Props>(), {
    showElo: false,
    showStats: false,
});
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="pb-2">
            <CardTitle class="truncate text-lg">{{
                character.fullname
            }}</CardTitle>
            <p
                v-if="character.nickname"
                class="truncate text-sm text-muted-foreground"
            >
                {{ character.nickname }}
            </p>
        </CardHeader>
        <CardContent class="flex-grow pb-2">
            <div
                class="relative aspect-square w-full overflow-hidden rounded-lg border"
            >
                <img
                    v-if="character.picture_url"
                    :src="character.picture_url"
                    :alt="character.fullname"
                    class="h-full w-full object-cover"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center bg-muted"
                >
                    <span class="text-muted-foreground">No image</span>
                </div>
            </div>
            <div v-if="props.showElo" class="mt-2">
                <Badge variant="secondary"
                    >ELO: {{ character.elo_rating ?? 'N/A' }}</Badge
                >
            </div>
        </CardContent>
        <CardFooter
            v-if="props.showStats"
            class="pt-2 text-xs text-muted-foreground"
        >
            <!-- Mostrar estadísticas si están disponibles en CharacterResource -->
            <!-- Asumiendo que CharacterResource podría tener estos campos si se cargan -->
            <div class="flex w-full flex-col">
                <div class="flex justify-between">
                    <span>Matches:</span>
                    <span>{{ character.matches_played ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Wins:</span>
                    <span>{{ character.wins ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Losses:</span>
                    <span>{{ character.losses ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Ties:</span>
                    <span>{{ character.ties ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Win Rate:</span>
                    <span>{{
                        character.win_rate
                            ? character.win_rate.toFixed(2) + '%'
                            : 'N/A'
                    }}</span>
                </div>
            </div>
        </CardFooter>
    </Card>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
