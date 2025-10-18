<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import type { CharacterResource } from '@/types/global'; // Asumiendo que CharacterResource tiene id, fullname, picture
import { useForm } from '@inertiajs/vue3';
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
// Opción 1: Hacer una solicitud al backend para obtener la próxima combinación
// Este endpoint debe usar CombinatoricService->getNextCombination
// y devolver la estructura de la combinación o null si no hay más.
// const fetchNextCombination = async () => { ... }

// Opción 2: (Más eficiente para el backend) Obtener la *próxima* combinación
// *junto con la respuesta de éxito* del voto anterior.
// Por ahora, implementaremos una llamada separada para simplificar, pero
// idealmente se integraría con la respuesta de `voteForm.post`.

// Simulamos la carga de la primera combinación al montar el componente
// En la práctica, llamarías a una API aquí.
onMounted(() => {
    loadNextCombination();
});

const loadNextCombination = async () => {
    if (noMoreCombinations.value) return;

    isLoading.value = true;
    try {
        // --- Simulación de llamada API ---
        // En la práctica, usarías `axios` o `fetch` para llamar a un endpoint como:
        // const response = await axios.get(`/api/surveys/${props.surveyId}/next-combination`);
        // if (response.data.combination) {
        //     currentCombination.value = response.data.combination;
        //     noMoreCombinations.value = false;
        // } else {
        //     currentCombination.value = null;
        //     noMoreCombinations.value = true;
        // }

        // Simulación temporal: Supongamos que obtenemos una combinación del backend
        // Debes reemplazar esta lógica con una llamada real.
        // Ejemplo real (requiere crear el endpoint en el backend):
        // const { data } = await axios.get(`/api/surveys/${props.surveyId}/next-combination`);
        // currentCombination.value = data.combination;

        // Por ahora, simulamos una respuesta vacía o con datos fijos para desarrollo
        // Simulamos que hay una combinación
        currentCombination.value = {
            combinatoric_id: 999, // ID simulado
            character1: {
                id: 1,
                fullname: 'Character A',
                picture: 'https://placehold.co/200', // URL simulada
                // ... otros campos según CharacterResource
            },
            character2: {
                id: 2,
                fullname: 'Character B',
                picture: 'https://placehold.co/200', // URL simulada
                // ... otros campos según CharacterResource
            },
        };
        noMoreCombinations.value = false; // Indicamos que hay combinaciones

        // Simulamos que no hay más combinaciones
        // currentCombination.value = null;
        // noMoreCombinations.value = true;
    } catch (err) {
        console.error('Error loading next combination:', err);
        error('Failed to load next combination. Please try again.');
        // Opcional: Podrías querer mostrar un botón para reintentar
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

    const { combinatoric_id } = currentCombination.value;

    // Configurar los datos del formulario para empate
    voteForm.combinatoric_id = combinatoric_id;
    voteForm.winner_id = 0; // o null, dependiendo de la API backend
    voteForm.loser_id = 0; // o null, dependiendo de la API backend
    voteForm.tie = true;

    // Enviar el voto
    submitVote();
};

const submitVote = () => {
    voteForm.post(route('surveys.vote.store', props.surveyId), {
        preserveScroll: true,
        onSuccess: (page) => {
            // page contiene la respuesta del backend
            success('Vote recorded successfully!');
            // Limpiar el formulario de votos anteriores
            voteForm.reset();
            // Opcional: Limpiar la combinación actual mostrada
            currentCombination.value = null;

            // --- OPCIÓN A: Recargar la siguiente combinación ---
            // Esto implica una nueva solicitud al backend
            loadNextCombination();

            // --- OPCIÓN B: El backend devuelve la siguiente combinación ---
            // Si el backend devuelve la próxima combinación en la respuesta de éxito,
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
                        :src="currentCombination.character1.picture"
                        :alt="currentCombination.character1.fullname"
                        class="mb-4 h-32 w-32 rounded-full border-4 border-indigo-500 object-cover"
                    />
                    <h3 class="text-lg font-semibold">
                        {{ currentCombination.character1.fullname }}
                    </h3>
                    <Button
                        @click="handleVote(currentCombination.character1.id)"
                        class="mt-2 bg-indigo-600 hover:bg-indigo-700"
                    >
                        Votar
                    </Button>
                </div>

                <!-- VS -->
                <div class="text-2xl font-bold text-gray-500">VS</div>

                <!-- Personaje 2 -->
                <div class="flex flex-col items-center">
                    <img
                        :src="currentCombination.character2.picture"
                        :alt="currentCombination.character2.fullname"
                        class="mb-4 h-32 w-32 rounded-full border-4 border-pink-500 object-cover"
                    />
                    <h3 class="text-lg font-semibold">
                        {{ currentCombination.character2.fullname }}
                    </h3>
                    <Button
                        @click="handleVote(currentCombination.character2.id)"
                        class="mt-2 bg-pink-600 hover:bg-pink-700"
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
