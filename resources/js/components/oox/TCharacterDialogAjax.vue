<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog'
import { Link } from '@inertiajs/vue3'
import type { CharacterResource } from '@/types/global'

interface Character extends CharacterResource {
    character: CharacterResource
}

interface Props {
    characterId: number
    /**
     * Si es true, el componente volverá a llamar a la API
     * cada vez que se abra el diálogo.
     * Si es false (por defecto), solo carga una vez (lazy + cache).
     */
    reloadOnOpen?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    reloadOnOpen: false,
})

// Estado interno
const open = ref(false)
const loading = ref(false)
const error = ref<string | null>(null)
const character = ref<CharacterResource | null>(null)

// ¿tenemos datos cargados?
const hasCharacter = computed(() => character.value !== null)

// Reset interno (útil cuando cambia characterId o cuando queremos forzar reload)
function resetState() {
    character.value = null
    error.value = null
    loading.value = false
}

// Cargar character (respeta reloadOnOpen)
async function loadCharacter() {
    loading.value = true
    error.value = null

    try {
        // Ajusta si tu backend envuelve la data
        const response = await axios.get<Character>(route('ajax.character.info', props.characterId))
        character.value = response.data.character
    } catch (e) {
        // console.error(e)
        error.value = 'Unable to load character information. Please try again.'
    } finally {
        loading.value = false
    }
}

// Garantizar datos antes de mostrar contenido
async function ensureCharacterLoaded() {
    // Si queremos reload en cada apertura, siempre llamamos a loadCharacter
    if (props.reloadOnOpen) {
        await loadCharacter()
        return
    }

    // Si NO queremos reload en cada apertura:
    // - si ya hay datos o ya se está cargando, no hacemos nada
    if (hasCharacter.value || loading.value) return

    await loadCharacter()
}

// Handler cuando cambia el estado de apertura del Dialog
async function handleOpenChange(value: boolean) {
    open.value = value
    if (value) {
        await ensureCharacterLoaded()
    }
}

// Si el characterId cambia, reseteamos el estado
watch(
    () => props.characterId,
    () => {
        resetState()
        // Opcional: si el diálogo está abierto cuando cambia el id, recargar inmediatamente
        if (open.value) {
            ensureCharacterLoaded()
        }
    }
)

// Texto de género
const genderLabel = computed(() => {
    const g = character.value?.gender
    if (g === 0) return 'Other'
    if (g === 1) return 'Male'
    if (g === 2) return 'Female'
    if (g === 3) return 'Non-binary'
    return 'Unknown'
})
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <!-- Trigger: slot + fallback -->
        <DialogTrigger asChild>
            <slot name="trigger">
                <button type="button" class="text-sm hover:text-muted-foreground cursor-pointer">
                    View character
                </button>
            </slot>
        </DialogTrigger>

        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{ hasCharacter ? character!.fullname : 'Loading character...' }}
                </DialogTitle>
                <DialogDescription v-if="hasCharacter">
                    {{ character!.nickname }}
                </DialogDescription>
                <DialogDescription v-else>
                    ...
                </DialogDescription>
            </DialogHeader>

            <!-- Estados: error / loading / contenido -->
            <div class="min-h-[200px] flex items-center justify-center" v-if="error">
                <p class="text-sm text-destructive text-center">
                    {{ error }}
                </p>
            </div>

            <div class="min-h-[200px] flex items-center justify-center" v-else-if="loading">
                <p class="text-sm text-muted-foreground">
                    Loading character information...
                </p>
            </div>

            <div v-else-if="hasCharacter" class="flex flex-col text-sm gap-4 items-center">
                <img :src="character!.picture_url" :alt="character!.fullname"
                    class="h-64 w-64 rounded-full object-cover" />

                <div>
                    <p class="text-sm text-muted-foreground line-clamp-3 mb-4">
                        {{ character!.bio }}
                    </p>

                    <dl class="divide-y divide-gray-100 dark:divide-white/10">
                        <!-- Gender -->
                        <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm/6 font-medium">
                                Gender
                            </dt>
                            <dd class="mt-1 text-sm/6 text-muted-foreground sm:col-span-2 sm:mt-0">
                                <div class="flex items-center gap-2">
                                    {{ genderLabel }}
                                </div>
                            </dd>
                        </div>

                        <!-- DOB -->
                        <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm/6 font-medium">
                                DOB
                            </dt>
                            <dd class="mt-1 text-sm/6 sm:col-span-2 sm:mt-0">
                                <div class="flex items-center gap-2 text-muted-foreground">
                                    {{ character!.dob_formatted || 'N/A' }}
                                    <span class="text-xs text-muted-foreground/70">
                                        ({{ character!.dob_for_humans || 'N/A' }})
                                    </span>
                                </div>
                            </dd>
                        </div>

                        <!-- Status -->
                        <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm/6 font-medium">
                                Status
                            </dt>
                            <dd class="mt-1 text-sm/6 sm:col-span-2 sm:mt-0 text-muted-foreground">
                                {{ character!.status === true ? 'Active' : 'Inactive' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <DialogFooter v-if="hasCharacter">
                <Link :href="route('public.characters.show', character!.id)">
                <Button type="button" variant="outline">
                    View Character
                </Button>
                </Link>

                <DialogClose as-child>
                    <Button type="button" variant="outline">
                        Close
                    </Button>
                </DialogClose>
            </DialogFooter>

            <DialogFooter v-else-if="error">
                <DialogClose as-child>
                    <Button type="button" variant="outline">
                        Close
                    </Button>
                </DialogClose>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
