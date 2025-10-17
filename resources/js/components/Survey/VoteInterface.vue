<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// Definir props
interface Props {
    surveyId: number; // ID de la encuesta activa
}
const props = defineProps<Props>();

// Inicializar toast
const { success, error } = useToast();

// Estado local para las parejas de personajes
const currentCombination = ref<{ character1: any; character2: any } | null>(
    null,
); // Ajustar tipo según CharacterResource
const isLoading = ref(false);
const noMoreCombinations = ref(false);

// Inicializar Inertia Form para el voto
const voteForm = useForm({
    combinatoric_id: 0, // Se llenará dinámicamente
    winner_id: 0, // Se llenará al hacer clic
    loser_id: 0, // Se llenará al hacer clic
    tie: false, // Se llenará al hacer clic
});

// --- Lógica de obtención de pareja ---
// Opción 1: Hacer una llamada API al backend para obtener la próxima pareja
// const fetchNextCombination = async () => { ... }

// Opción 2: El backend ya pasó la pareja inicial, y el frontend maneja la lógica de mostrar la siguiente
// (esto implica que el backend calcule la siguiente pareja en cada respuesta de voto, lo cual es menos eficiente)
// La opción 1 es más común y eficiente para el backend.

// Para esta implementación, asumiremos que hay un endpoint que devuelve la próxima combinación
// basada en el ID de la encuesta y el usuario autenticado (lo cual ya está implementado en el backend).
// Simularemos la obtención de la primera pareja al montar el componente.
// En una implementación real, llamarías a una API aquí o recibirías la primera pareja del padre.

onMounted(async () => {
    loadNextCombination();
});

const loadNextCombination = async () => {
    if (noMoreCombinations.value) return;

    isLoading.value = true;
    try {
        // Simular una llamada API para obtener la próxima pareja
        // En la práctica, usarías axios o fetch para llamar a un endpoint como
        // GET /api/surveys/{surveyId}/next-combination
        // y ese endpoint usaría CombinatoricService->getNextCombination
        // Por ahora, simulamos una respuesta:
        // const response = await axios.get(`/api/surveys/${props.surveyId}/next-combination`);
        // currentCombination.value = response.data.combination;

        // Simulación:
        // Supongamos que recibimos datos de personajes desde el backend o se cargan previamente
        // Por simplicidad, usaremos datos fijos para la demostración
        // Deberías reemplazar esta lógica con una llamada real al backend.
        // Por ejemplo, podrías tener un servicio JS que haga la llamada y lo inyectes aquí.
        // const combinationData = await fetchNextCombinationFromApi(props.surveyId);
        // currentCombination.value = combinationData;

        // Simulación temporal: Mostrar un mensaje o estructura vacía
        // currentCombination.value = {
        //     character1: { id: 1, fullname: 'Character 1', picture: '...' },
        //     character2: { id: 2, fullname: 'Character 2', picture: '...' },
        //     combinatoric_id: 10 // ID de la combinación específica
        // };
        console.log(
            'Cargando próxima combinación para la encuesta:',
            props.surveyId,
        );
        // Simular carga exitosa o fallo (por ejemplo, no hay más combinaciones)
        // Por ahora, simulamos que no hay más combinaciones para simplificar
        noMoreCombinations.value = true;
        currentCombination.value = null;
    } catch (err) {
        console.error('Error loading next combination:', err);
        error('Failed to load next combination. Please try again.');
        noMoreCombinations.value = true; // O manejar el error de otra manera
        currentCombination.value = null;
    } finally {
        isLoading.value = false;
    }
};

// --- Lógica de manejo de voto ---
const handleVote = (characterId: number) => {
    if (!currentCombination.value || isLoading.value) return;

    // Lógica para determinar ganador/perdedor
    // Suponiendo que currentCombination.value tiene { character1, character2, combinatoric_id }
    const combinatoricId = currentCombination.value.combinatoric_id; // Asumiendo que el backend lo provee
    const char1Id = currentCombination.value.character1.id;
    const char2Id = currentCombination.value.character2.id;

    if (characterId === char1Id) {
        voteForm.combinatoric_id = combinatoricId;
        voteForm.winner_id = char1Id;
        voteForm.loser_id = char2Id;
        voteForm.tie = false;
    } else if (characterId === char2Id) {
        voteForm.combinatoric_id = combinatoricId;
        voteForm.winner_id = char2Id;
        voteForm.loser_id = char1Id;
        voteForm.tie = false;
    } else {
        // Si characterId no coincide con ninguno, no hacer nada o lanzar error
        error('Invalid character selection.');
        return;
    }

    submitVote();
};

const handleTie = () => {
    if (!currentCombination.value || isLoading.value) return;

    const combinatoricId = currentCombination.value.combinatoric_id;
    const char1Id = currentCombination.value.character1.id;
    const char2Id = currentCombination.value.character2.id;

    voteForm.combinatoric_id = combinatoricId;
    voteForm.winner_id = 0; // o null
    voteForm.loser_id = 0; // o null
    voteForm.tie = true;

    submitVote();
};

const submitVote = () => {
    voteForm.post(route('surveys.vote.store', props.surveyId), {
        preserveScroll: true,
        onSuccess: () => {
            success('Vote recorded successfully!');
            // Limpiar el formulario
            voteForm.reset();
            // Cargar la próxima combinación
            loadNextCombination();
        },
        onError: (errors) => {
            console.error('Errors submitting vote:', errors);
            error('Failed to submit vote. Please check the errors below.');
            // El helper `useForm` maneja automáticamente la visualización de errores
            // en el componente si se usa `voteForm.errors.fieldName`
        },
        onFinish: () => {
            // Acciones que se realizan siempre al finalizar (éxito o error)
        },
    });
};

// --- Lógica para mostrar la interfaz ---
const renderVotingInterface = () => {
    if (isLoading.value) {
        return <div class="py-8 text-center">Loading next match...</div>;
    }

    if (noMoreCombinations.value || !currentCombination.value) {
        return (
            <div class="py-8 text-center">
                No more combinations! You have voted on all available matches.
            </div>
        );
    }

    const { character1, character2 } = currentCombination.value;

    return (
        <div class="flex flex-col items-center justify-center gap-8 md:flex-row">
            {/* Personaje 1 */}
            <div class="flex flex-col items-center">
                <img
                    src={character1.picture}
                    alt={character1.fullname}
                    class="mb-4 h-32 w-32 rounded-full object-cover"
                />
                <h3 class="text-lg font-semibold">{character1.fullname}</h3>
                <Button onClick={() => handleVote(character1.id)} class="mt-2">
                    Votar
                </Button>
            </div>

            {/* VS */}
            <div class="text-2xl font-bold">VS</div>

            {/* Personaje 2 */}
            <div class="flex flex-col items-center">
                <img
                    src={character2.picture}
                    alt={character2.fullname}
                    class="mb-4 h-32 w-32 rounded-full object-cover"
                />
                <h3 class="text-lg font-semibold">{character2.fullname}</h3>
                <Button onClick={() => handleVote(character2.id)} class="mt-2">
                    Votar
                </Button>
            </div>

            {/* Botón de Empate */}
            <div class="mt-4 md:mt-0">
                <Button variant="outline" onClick={handleTie}>
                    Empate
                </Button>
            </div>
        </div>
    );
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Encuentro Actual</CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="renderVotingInterface">{ renderVotingInterface() }</div>
        </CardContent>
    </Card>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
