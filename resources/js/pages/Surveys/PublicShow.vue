<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    CharactersData,
    SurveyResource,
    UserSurveyProgress,
} from '@/types/global'; // Tipos actualizados
import { Head, Link } from '@inertiajs/vue3';

interface Props {
    survey: SurveyResource; // Detalles de la encuesta
    characters: CharactersData; // Personajes participantes
    userProgress: UserSurveyProgress; // Estado del usuario en la encuesta
}

const props = defineProps<Props>();

// Determinar si el usuario puede votar (no ha completado la encuesta)
const canVote = !props.userProgress.is_completed;
</script>

<template>
    <Head :title="`Encuesta: ${survey.title}`" />

    <div class="min-h-screen bg-background py-12 text-foreground">
        <div class="container mx-auto px-4">
            <!-- Tarjeta de Detalles de la Encuesta -->
            <Card class="mx-auto mb-8 max-w-4xl">
                <CardHeader>
                    <CardTitle>{{ survey.title }}</CardTitle>
                    <CardDescription>{{ survey.description }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <h4 class="font-medium">Categoría</h4>
                            <p>{{ survey.category.name }}</p>
                        </div>
                        <div>
                            <h4 class="font-medium">Fechas</h4>
                            <p>
                                {{
                                    new Date(
                                        survey.date_start,
                                    ).toLocaleDateString()
                                }}
                                -
                                {{
                                    new Date(
                                        survey.date_end,
                                    ).toLocaleDateString()
                                }}
                            </p>
                        </div>
                        <div>
                            <h4 class="font-medium">Estado</h4>
                            <p
                                :class="{
                                    'text-green-600': survey.status,
                                    'text-red-600': !survey.status,
                                }"
                            >
                                {{ survey.status ? 'Activa' : 'Inactiva' }}
                            </p>
                        </div>
                        <div>
                            <h4 class="font-medium">Progreso</h4>
                            <p>
                                {{ userProgress.progress.toFixed(2) }}% ({{
                                    userProgress.total_votes
                                }}/{{ userProgress.total_expected }} votos)
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tarjeta de Personajes Participantes -->
            <Card class="mx-auto mb-8 max-w-4xl">
                <CardHeader>
                    <CardTitle>Personajes Participantes</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4">
                        <div
                            v-for="character in props.characters.data"
                            :key="character.id"
                            class="flex flex-col items-center"
                        >
                            <img
                                :src="character.picture_url"
                                :alt="character.fullname"
                                class="mb-2 h-16 w-16 rounded-full object-cover"
                            />
                            <span class="text-sm">{{
                                character.fullname
                            }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tarjeta de Interfaz de Votación -->
            <Card class="mx-auto max-w-4xl">
                <CardHeader>
                    <CardTitle>Vota</CardTitle>
                    <CardDescription
                        >Elige al personaje que creas que gana.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div v-if="canVote">
                        <!-- Renderizar el componente de votación -->
                        <!-- Pasamos el ID de la encuesta y cualquier otro dato necesario -->
                        <VoteInterface :survey-id="survey.id" />
                    </div>
                    <div v-else class="py-8 text-center">
                        <p class="text-lg">¡Has completado esta encuesta!</p>
                        <Link :href="route('surveys.public.index')">
                            <Button variant="outline" class="mt-4"
                                >Ver otras encuestas</Button
                            >
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
