<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import type { CharacterResource } from '@/types/global'; // Asumiendo que CharacterResource tiene id, fullname, picture
import { useForm } from '@inertiajs/vue3';
import axios from 'axios'; // Importar axios para hacer la solicitud HTTP
import { onMounted, ref } from 'vue';

// --- Tipos ---
// Definimos un tipo para la combinación actual que esperamos recibir del backend
interface CurrentCombination {
    combinatoric_id: number;
    character1: CharacterResource;
    character2: CharacterResource;
}

// --- Props ---
interface Props {
    surveyId: number; // ID de la encuesta activa
}
const props = defineProps<Props>();

// --- Inicializar toast ---
const { success, error } = useToast();

// --- Estado Local ---
const currentCombination = ref<CurrentCombination | null>(null);
const isLoading = ref(false);
const noMoreCombinations = ref(false);

// --- Inertia Form para el voto ---
// Inicializamos el formulario con valores vacíos
const voteForm = useForm({
    combinatoric_id: 0,
    winner_id: 0,
    loser_id: 0,
    tie: false,
});

// --- Lógica de Carga de la Próxima Combinación ---
// Llama al endpoint del backend para obtener la próxima combinación
const loadNextCombination = async () => {
    if (noMoreCombinations.value) {
        // Si ya se sabe que no hay más, no intentar cargar
        return;
    }

    isLoading.value = true;
    try {
        // Hacer la solicitud GET al endpoint
        // La URL se construye usando el ID de la encuesta
        const response = await axios.get(
            route('api.public.surveys.next_combination', props.surveyId),
        );
        /* `/api/public/surveys/${props.surveyId}/next-combination`,
        ); */

        // Verificar si la respuesta indica que no hay más combinaciones
        if (response.data.combination === null) {
            currentCombination.value = null;
            noMoreCombinations.value = true;
        } else {
            // Si hay una combinación, actualizar el estado
            currentCombination.value = response.data.combination;
            noMoreCombinations.value = false; // Asegurar que este flag esté en false si hay combinación
        }
    } catch (err: any) {
        // Usar 'any' para manejar el error genéricamente
        console.error('Error loading next combination:', err);
        // Mostrar mensaje de error al usuario
        error('Failed to load next combination. Please try again.');
        // Opcional: Podrías querer mostrar un botón para reintentar o detener la carga
        currentCombination.value = null;
        noMoreCombinations.value = true; // Detenemos la carga si falla
    } finally {
        isLoading.value = false;
    }
};

// --- Lógica de Manejo de Voto ---
const handleVote = (selectedCharacterId: number) => {
    if (!currentCombination.value || isLoading.value) return;

    const { combinatoric_id, character1, character2 } =
        currentCombination.value;

    // Verificar que el ID seleccionado sea uno de los dos personajes de la combinación
    if (
        selectedCharacterId !== character1.id &&
        selectedCharacterId !== character2.id
    ) {
        error('Invalid character selection.');
        return;
    }

    // Determinar ganador y perdedor
    const winner_id = selectedCharacterId;
    const loser_id =
        selectedCharacterId === character1.id ? character2.id : character1.id;

    // Configurar los datos del formulario
    voteForm.combinatoric_id = combinatoric_id;
    voteForm.winner_id = winner_id;
    voteForm.loser_id = loser_id;
    voteForm.tie = false; // No es empate

    // Enviar el voto
    submitVote();
};

const handleTie = () => {
    if (!currentCombination.value || isLoading.value) return;

    const combinatoricId = currentCombination.value.combinatoric_id;

    // Configurar los datos del formulario para empate
    // voteForm.combinatoric_id = combinatoricId;
    // voteForm.winner_id = 0; // o null, dependiendo de la API backend
    // voteForm.loser_id = 0; // o null, dependiendo de la API backend
    // voteForm.tie = true;

    // resetea los campos que no aplican
    voteForm.reset(); // Limpia todos los campos
    voteForm.combinatoric_id = combinatoricId; // Vuelve a setear los necesarios
    voteForm.tie = true; // Solo setea tie

    // Enviar el voto
    submitVote();
};

const submitVote = () => {
    voteForm.post(route('surveys.vote.store', props.surveyId), {
        preserveScroll: true,
        onSuccess: (page) => {
            // page contiene la respuesta del backend (page.props.flash, etc.)
            success('Vote recorded successfully!');
            // Limpiar el formulario de votos anteriores
            voteForm.reset();
            // Opcional: Limpiar la combinación actual mostrada (esto lo hará la recarga)
            // currentCombination.value = null;

            // --- OPCIÓN A: Recargar la siguiente combinación desde el backend ---
            // Esto implica una nueva solicitud HTTP despues de procesar el voto exitosamente.
            loadNextCombination(); // <-- Llamamos a la función que hace la solicitud GET

            // --- OPCIÓN B (No implementada aquí): El backend devuelve la siguiente combinación ---
            // Si SurveyVoteController@store devolviera la próxima combinación en la respuesta,
            // podrías hacer algo como:
            // const nextCombination = page.props.nextCombination; // Asumiendo que el backend lo envía
            // if (nextCombination) {
            //     currentCombination.value = nextCombination;
            //     noMoreCombinations.value = false;
            // } else {
            //     noMoreCombinations.value = true;
            //     currentCombination.value = null;
            // }
            // isLoading.value = false; // Si manejas isLoading aquí también
        },
        onError: (errors) => {
            console.error('Errors submitting vote:', errors);
            error('Failed to submit vote. Please check the errors below.');
            // useForm maneja automáticamente los errores en voteForm.errors
        },
        onFinish: () => {
            // Acciones que se realizan siempre al finalizar (éxito o error)
            // isLoading.value se maneja internamente o se puede reiniciar aquí si es necesario
            // isLoading.value = false; // Si se pone isLoading = true antes de post, descomentar
        },
    });
};

// --- Cargar la primera combinación cuando el componente se monte ---
onMounted(() => {
    loadNextCombination();
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Encuentro Actual</CardTitle>
        </CardHeader>
        <CardContent>
            <!-- Indicador de Carga -->
            <div v-if="isLoading" class="py-8 text-center">
                Loading next match...
            </div>

            <!-- Mensaje de Fin de Encuesta -->
            <div
                v-else-if="noMoreCombinations || !currentCombination"
                class="py-8 text-center"
            >
                No more combinations! You have voted on all available matches.
            </div>

            <!-- Interfaz de Votación -->
            <div
                v-else
                class="flex flex-col items-center justify-center gap-8 md:flex-row"
            >
                <!-- Personaje 1 -->
                <div class="flex flex-col items-center">
                    <img
                        :src="currentCombination.character1.picture_url"
                        :alt="currentCombination.character1.fullname"
                        class="mb-4 h-32 w-32 rounded-full border-4 border-indigo-500 object-cover"
                    />
                    <h3 class="text-lg font-semibold">
                        {{ currentCombination.character1.fullname }}
                    </h3>
                    <Button
                        @click="handleVote(currentCombination.character1.id)"
                        class="mt-2 bg-indigo-600 hover:bg-indigo-700"
                        :disabled="voteForm.processing"
                    >
                        Votar
                    </Button>
                </div>

                <!-- VS -->
                <div class="text-2xl font-bold text-gray-500">VS</div>

                <!-- Personaje 2 -->
                <div class="flex flex-col items-center">
                    <img
                        :src="currentCombination.character2.picture_url"
                        :alt="currentCombination.character2.fullname"
                        class="mb-4 h-32 w-32 rounded-full border-4 border-pink-500 object-cover"
                    />
                    <h3 class="text-lg font-semibold">
                        {{ currentCombination.character2.fullname }}
                    </h3>
                    <Button
                        @click="handleVote(currentCombination.character2.id)"
                        class="mt-2 bg-pink-600 hover:bg-pink-700"
                        :disabled="voteForm.processing"
                    >
                        Votar
                    </Button>
                </div>

                <!-- Botón de Empate -->
                <div class="mt-4 md:mt-0">
                    <Button
                        variant="outline"
                        @click="handleTie"
                        :disabled="voteForm.processing"
                    >
                        Empate
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
