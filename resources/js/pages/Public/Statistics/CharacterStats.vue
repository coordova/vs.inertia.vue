<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { CharacterStatsResource/* , CategoryCharacterStatResource, CharacterSurveyParticipationResource */ } from '@/types/global'; // Tipos actualizados
import { Pencil, Trash, Eye } from 'lucide-vue-next'; // Iconos
import { ref, computed } from 'vue'; // Para manejar estado local si es necesario (ej: paginación de encuestas)

// --- Tipos ---
interface Props {
    character: CharacterStatsResource; // El personaje con sus estadísticas por categoría y encuesta
}

const props = defineProps<Props>();

console.log(props);
// console.log(props.character.surveys_participation);

// --- Composables ---
const { success, error } = useToast();

// --- Computed Properties ---
// Calcular win rate total (promedio ponderado o simple, dependiendo de la lógica de negocio deseada)
// Por ejemplo, un win rate simple basado en todas las categorías donde ha jugado
// const totalWins = computed(() => props.character.categories_stats?.reduce((sum, stat) => sum + stat.wins, 0) ?? 0);
// const totalLosses = computed(() => props.character.categories_stats?.reduce((sum, stat) => sum + stat.losses, 0) ?? 0);
// const totalTies = computed(() => props.character.categories_stats?.reduce((sum, stat) => sum + stat.ties, 0) ?? 0);
// const totalMatches = computed(() => totalWins.value + totalLosses.value + totalTies.value);
// const overallWinRate = computed(() => totalMatches.value > 0 ? (totalWins.value / totalMatches.value) * 100 : 0);

// --- Computed Properties para estadísticas generales ---
// Calcular win rate total (promedio simple basado en todas las categorías donde ha jugado)
const totalWins = computed(() => {
    const stats = props.character.categories_stats;
    return Array.isArray(stats) ? stats.reduce((sum, stat) => sum + stat.wins, 0) : 0; // <-- CORRECCIÓN: Verificar isArray
});

const totalLosses = computed(() => {
    const stats = props.character.categories_stats;
    return Array.isArray(stats) ? stats.reduce((sum, stat) => sum + stat.losses, 0) : 0; // <-- CORRECCIÓN: Verificar isArray
});

const totalTies = computed(() => {
    const stats = props.character.categories_stats;
    return Array.isArray(stats) ? stats.reduce((sum, stat) => sum + stat.ties, 0) : 0; // <-- CORRECCIÓN: Verificar isArray
});

const totalMatches = computed(() => totalWins.value + totalLosses.value + totalTies.value);
const overallWinRate = computed(() => totalMatches.value > 0 ? (totalWins.value / totalMatches.value) * 100 : 0);


// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
    /* {
        title: 'Characters',
        href: route('public.characters.index'), // Asumiendo una ruta de listado público
    },
    {
        title: props.character.fullname, // Nombre dinámico del personaje
        href: route('public.characters.show', props.character.slug), // Asumiendo una ruta de vista pública
    },
    {
        title: 'Stats',
        href: route('public.characters.stats', props.character.slug), // Ruta actual
    }, */
];
</script>

<template>

    <Head :title="`Stats: ${character.fullname}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8">
            <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <header class="border-b pb-4">
                    <div class="container flex h-16 items-center justify-between px-4">
                        <h1 class="text-xl font-semibold">{{ character.fullname }}</h1>
                        <Badge :variant="character.status ? 'default' : 'secondary'">
                            {{ character.status ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                </header>

                <main class="container py-8">
                    <!-- Información Principal del Personaje -->
                    <Card class="mb-8">
                        <CardHeader>
                            <CardTitle>Character Information</CardTitle>
                            <CardDescription>
                                Basic details about {{ character.fullname }}.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-start gap-6">
                                <div class="relative aspect-square w-32 overflow-hidden rounded-full border">
                                    <img v-if="character.picture_url" :src="character.picture_url"
                                        :alt="character.fullname" class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full w-full items-center justify-center bg-muted">
                                        <span class="text-muted-foreground">No image</span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h2 class="text-2xl font-bold">{{ character.fullname }}</h2>
                                    <p v-if="character.nickname" class="text-lg text-muted-foreground">{{
                                        character.nickname }}</p>
                                    <p v-if="character.bio" class="mt-2 text-sm">{{ character.bio }}</p>
                                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <h4 class="font-medium text-muted-foreground">Date of Birth</h4>
                                            <p>{{ character.dob_formatted || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-muted-foreground">Gender</h4>
                                            <p>
                                                {{
                                                    character.gender === 0 ? 'Other' :
                                                        character.gender === 1 ? 'Male' :
                                                            character.gender === 2 ? 'Female' :
                                                                character.gender === 3 ? 'Non-binary' : 'Unknown'
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-muted-foreground">Nationality</h4>
                                            <p>{{ character.nationality || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-muted-foreground">Occupation</h4>
                                            <p>{{ character.occupation || 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Estadísticas Generales (Resumen por Categorías) -->
                    <Card class="mb-8">
                        <CardHeader>
                            <CardTitle>Overall Statistics</CardTitle>
                            <CardDescription>
                                Summary of performance across all categories.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ totalMatches }}</div>
                                    <div class="text-sm text-muted-foreground">Total Matches</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ totalWins }}</div>
                                    <div class="text-sm text-muted-foreground">Wins</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ totalLosses }}</div>
                                    <div class="text-sm text-muted-foreground">Losses</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ totalTies }}</div>
                                    <div class="text-sm text-muted-foreground">Ties</div>
                                </div>
                            </div>
                            <div class="mt-4 text-center">
                                <div class="text-xl font-semibold">{{ overallWinRate.toFixed(2) }}%</div>
                                <div class="text-sm text-muted-foreground">Overall Win Rate</div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Estadísticas por Categoría -->
                    <Card class="mb-8">
                        <CardHeader>
                            <CardTitle>Statistics by Category</CardTitle>
                            <CardDescription>
                                Performance details within each category.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="overflow-x-auto rounded-lg border">
                                <Table>
                                    <TableCaption>Rating history and performance metrics per category.</TableCaption>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Category</TableHead>
                                            <TableHead>ELO Rating</TableHead>
                                            <TableHead>Matches Played</TableHead>
                                            <TableHead>Wins</TableHead>
                                            <TableHead>Losses</TableHead>
                                            <TableHead>Ties</TableHead>
                                            <TableHead>Win Rate</TableHead>
                                            <TableHead>Peak Rating</TableHead>
                                            <TableHead>Last Match</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-if="!props.character.categories_stats || props.character.categories_stats.length === 0">
                                            <TableCell :colSpan="9" class="h-24 text-center">
                                                No category participation records found.
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-else v-for="stat in props.character.categories_stats"
                                            :key="`${stat.category_id}-${stat.character_id}`">
                                            <TableCell class="font-medium">{{ stat.category?.name || `Category
                                                ${stat.category_id}` }}</TableCell>
                                            <TableCell>{{ stat.elo_rating/* .toFixed(2) */ }}</TableCell>
                                            <TableCell>{{ stat.matches_played }}</TableCell>
                                            <TableCell>{{ stat.wins }}</TableCell>
                                            <TableCell>{{ stat.losses }}</TableCell>
                                            <TableCell>{{ stat.ties }}</TableCell> <!-- Mostrar empates -->
                                            <TableCell>{{ stat.win_rate/* .toFixed(2) */ }}%</TableCell>
                                            <TableCell>{{ stat.highest_rating }}</TableCell>
                                            <TableCell>{{ stat.last_match_at ? new
                                                Date(stat.last_match_at).toLocaleDateString() : 'Never' }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Participación en Encuestas -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Survey Participation</CardTitle>
                            <CardDescription>
                                History of participation and results in specific surveys.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="overflow-x-auto rounded-lg border">
                                <Table>
                                    <TableCaption>Performance details within each survey participated.</TableCaption>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Survey</TableHead>
                                            <TableHead>Category</TableHead>
                                            <TableHead>Survey Matches</TableHead>
                                            <TableHead>Survey Wins</TableHead>
                                            <TableHead>Survey Losses</TableHead>
                                            <TableHead>Survey Ties</TableHead>
                                            <TableHead>Survey Position</TableHead>
                                            <TableHead class="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-if="!props.character.surveys_participation || props.character.surveys_participation.length === 0">
                                            <TableCell :colSpan="8" class="h-24 text-center">
                                                No survey participation records found.
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-else v-for="participation in props.character.surveys_participation"
                                            :key="`${participation.character_id}-${participation.survey_id}`">
                                            <TableCell class="font-medium">
                                                <Link :href="route('public.surveys.show', participation.survey.slug)"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                {{ participation.survey.title }}
                                                </Link>
                                            </TableCell>
                                            <TableCell>{{ participation.survey.category.name }}</TableCell>
                                            <!-- Acceder a la categoría de la encuesta -->
                                            <TableCell>{{ participation.survey_matches }}</TableCell>
                                            <TableCell>{{ participation.survey_wins }}</TableCell>
                                            <TableCell>{{ participation.survey_losses }}</TableCell>
                                            <TableCell>{{ participation.survey_ties }}</TableCell>
                                            <!-- Mostrar empates en encuesta -->
                                            <TableCell>{{ participation.survey_position || 'N/A' }}</TableCell>
                                            <!-- Posición en la encuesta -->
                                            <TableCell class="text-right">
                                                <Button asChild variant="outline" size="sm">
                                                    <Link
                                                        :href="route('public.surveys.results', participation.survey.slug)">
                                                    <!-- Asumiendo una ruta de resultados -->
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    View Results
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </main>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>