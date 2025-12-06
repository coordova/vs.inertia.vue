<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
// import { Separator } from '@/components/ui/separator';
// import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { SurveyResource, CharacterSurveyRankingResource } from '@/types/global'; // Tipos actualizados
import { Eye/* , Pencil, RotateCw, Trash */ } from 'lucide-vue-next'; // Iconos
import { /* ref, */ computed } from 'vue'; // Para manejar estado local si es necesario (ej: paginación)
import TCharacterDialogAjax from '@/components/oox/TCharacterDialogAjax.vue';

interface Props {
    survey: SurveyResource; // Datos de la encuesta
    ranking: CharacterSurveyRankingResource[]; // Ranking de personajes en la encuesta (sin paginación por ahora)
    /* ranking: { // Asumiendo que RankingService devuelve una colección paginada
        data: CharacterSurveyRankingResource[]; // Array de entradas de ranking
        meta: {
            current_page: number;
            from: number;
            last_page: number;
            path: string;
            per_page: number;
            to: number;
            total: number;
        };
        links: { url: string | null; label: string; active: boolean }[]; // Links de paginación
    }; */

    // Si se implementa paginación, el tipo sería SurveyResultsData
    // filters?: Record<string, any>; // Filtros aplicados (search, sort, per_page, page) - opcional
}

const props = defineProps<Props>();

console.log(props.survey);
console.log(props.ranking);

// --- Composables ---
// const { success, error } = useToast();

// --- Computed Properties para estadísticas generales ---
const totalVotes = computed(() => {
    // Sumar survey_matches de todos los personajes en el ranking
    // Asumiendo que survey_matches es el número total de votos en los que participó el personaje
    // Cada combinación implica un voto, y cada personaje de la combinación tiene +1 en survey_matches
    // Por lo tanto, sumar survey_matches de todos los personajes dará 2 * total_votos_encuesta
    // Para obtener el total de votos únicos, dividimos por 2.
    // O, si survey_matches es el número de *veces que el personaje fue mostrado*, entonces la suma es directa.
    // Asumiendo que survey_matches es el número de *votos* en los que participó el personaje (suma de wins, losses, ties).
    // Si cada voto implica que AMBOS personajes de la combinación aumenten survey_matches, entonces:
    // Total de votos = (Suma de survey_matches) / 2
    // Si cada voto implica que SOLO el ganador/perdedor aumente survey_matches (lo cual no parece ser el caso según el código de actualización), entonces:
    // Total de votos = Suma de survey_wins + survey_losses (no considera empates si no se suman)
    // La mejor forma es tener un campo calculado en SurveyResource o en SurveyResultsData
    // que devuelva el total de votos de la encuesta.
    // Por ahora, calculamos basado en la suposición: Cada voto incrementa survey_matches para 2 personajes.
    // Otra opción: Calcularlo en el backend y pasarlo como parte de props.survey o como un campo extra.
    // const totalMatchCounts = props.ranking.reduce((sum, item) => sum + item.survey_matches, 0);
    // return totalMatchCounts / 2; // Asumiendo que cada voto afecta a 2 personajes

    // Opción más directa si se tiene el total de votos en la encuesta:
    // return props.survey.total_votes; // Si SurveyResource incluye este campo calculado
    // Si SurveyResource no lo incluye, calcularlo aquí o usar otra métrica si está disponible.
    // Por ahora, asumimos que no se tiene directamente y lo dejamos como comentario.
    // return 0; // Placeholder

    // Calculo alternativo si cada voto incrementa matches de ambos personajes:
    // const totalMatchCounts = props.ranking.reduce((sum, item) => sum + item.survey_matches, 0);
    // return Math.floor(totalMatchCounts / 2); // Asumiendo 2 personajes por voto

    // Calculo basado en la suma de wins y losses (si cada voto genera un win y un loss)
    const totalWins = props.ranking.reduce((sum, item) => sum + item.survey_wins, 0);
    const totalLosses = props.ranking.reduce((sum, item) => sum + item.survey_losses, 0);
    // const totalTies = props.ranking.reduce((sum, item) => sum + item.survey_ties, 0); // Si se quiere incluir empates
    return totalWins + totalLosses; // Asumiendo que cada voto genera un win y un loss
});

const totalParticipants = computed(() => props.ranking.length);

// --- Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Surveys',
        href: route('public.surveys.index'),
    },
    {
        title: props.survey.title,
        href: route('public.surveys.show', props.survey.slug), // O props.survey.id
    },
    {
        title: 'Results',
        href: route('public.surveys.results', props.survey.slug), // O props.survey.id, si existe esta ruta
    },
];
</script>

<template>

    <Head :title="`Results: ${survey.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8">
            <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <header class="border-b pb-4">
                    <div class="container flex h-16 items-center justify-between px-4">
                        <h1 class="text-xl font-semibold">Survey Results: {{ survey.title }}</h1>
                        <Badge :variant="survey.status ? 'default' : 'secondary'">
                            {{ survey.status ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                </header>

                <main class="container py-8">
                    <!-- Información General de la Encuesta -->
                    <Card class="mb-8">
                        <CardHeader>
                            <CardTitle>Survey Information</CardTitle>
                            <CardDescription>
                                General details and statistics for this survey.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <h4 class="font-medium">Category</h4>
                                    <p>{{ survey.category.name }}</p>
                                </div>
                                <div>
                                    <h4 class="font-medium">Start Date</h4>
                                    <p>{{ new Date(survey.date_start).toLocaleDateString() }}</p>
                                </div>
                                <div>
                                    <h4 class="font-medium">End Date</h4>
                                    <p>{{ new Date(survey.date_end).toLocaleDateString() }}</p>
                                </div>
                                <div>
                                    <h4 class="font-medium">Strategy</h4>
                                    <p>{{ survey.selection_strategy }}</p>
                                </div>
                                <div>
                                    <h4 class="font-medium">Allow Ties</h4>
                                    <p>{{ survey.allow_ties ? 'Yes' : 'No' }}</p>
                                </div>
                                <div>
                                    <h4 class="font-medium">Tie Weight</h4>
                                    <p>{{ survey.tie_weight }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tabla de Ranking -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Ranking</CardTitle>
                            <CardDescription>
                                Final (or current) ranking based on votes in this survey.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="overflow-x-auto rounded-lg border">
                                <Table>
                                    <TableCaption>
                                        Showing {{ ranking.length }} of {{ ranking.length }} participants.
                                    </TableCaption>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead class="w-[100px]">Position</TableHead>
                                            <TableHead>Character</TableHead>
                                            <TableHead>Matches</TableHead>
                                            <TableHead>Wins</TableHead>
                                            <TableHead>Losses</TableHead>
                                            <TableHead>Ties</TableHead>
                                            <TableHead>Win Rate</TableHead>
                                            <TableHead class="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-if="ranking.length === 0">
                                            <TableCell :colSpan="8" class="h-24 text-center">
                                                No participants found for this survey.
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-else v-for="rankItem in ranking" :key="rankItem.character_id">
                                            <TableCell class="font-medium">{{ rankItem.survey_position }}</TableCell>
                                            <TableCell>
                                                <TCharacterDialogAjax :character-id="rankItem.character.id">
                                                    <template #trigger>
                                                        <div class="flex items-center gap-3">
                                                            <img v-if="rankItem.character.picture_url"
                                                                :src="rankItem.character.picture_url"
                                                                :alt="rankItem.character.fullname"
                                                                class="h-10 w-10 rounded-full object-cover" />
                                                            <div v-else
                                                                class="bg-muted h-10 w-10 rounded-full flex items-center justify-center">
                                                                <span class="text-muted-foreground text-xs">N/A</span>
                                                            </div>
                                                            <div>
                                                                <div class="font-medium">{{ rankItem.character.fullname
                                                                    }}
                                                                </div>
                                                                <div v-if="rankItem.character.nickname"
                                                                    class="text-sm text-muted-foreground">{{
                                                                        rankItem.character.nickname }}</div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </TCharacterDialogAjax>
                                            </TableCell>
                                            <TableCell>{{ rankItem.survey_matches }}</TableCell>
                                            <TableCell>{{ rankItem.survey_wins }}</TableCell>
                                            <TableCell>{{ rankItem.survey_losses }}</TableCell>
                                            <TableCell>{{ rankItem.survey_ties }}</TableCell> <!-- Mostrar empates -->
                                            <TableCell>
                                                <!-- Calcular win rate en el frontend -->
                                                {{
                                                    rankItem.survey_matches > 0
                                                        ? ((rankItem.survey_wins / rankItem.survey_matches) * 100).toFixed(2) +
                                                        '%'
                                                        : '0.00%'
                                                }}
                                            </TableCell>
                                            <TableCell class="text-right">
                                                <Button asChild variant="outline" size="sm">
                                                    <Link
                                                        :href="route('public.characters.show', rankItem.character_id)">
                                                    <!-- O rankItem.character.id -->
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    View
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                        <CardFooter class="flex justify-between">
                            <Button variant="outline" asChild>
                                <Link :href="route('public.surveys.show', survey.id)"> <!-- O survey.id -->
                                Back to Survey
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link :href="route('public.surveys.vote', survey.id)"> <!-- O survey.id -->
                                Participate
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>

                    <!-- Estadísticas Generales -->
                    <Card class="mt-8">
                        <CardHeader>
                            <CardTitle>General Statistics</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ totalParticipants }}</div>
                                    <div class="text-sm text-muted-foreground">Participants</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ totalVotes }}</div>
                                    <div class="text-sm text-muted-foreground">Total Votes</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ survey.date_start ? new
                                        Date(survey.date_start).toLocaleDateString() : 'N/A' }} - {{ survey.date_end ?
                                            new Date(survey.date_end).toLocaleDateString() : 'N/A' }}</div>
                                    <div class="text-sm text-muted-foreground">Duration</div>
                                </div>
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