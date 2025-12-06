<script setup lang="ts">
import { ref, computed } from 'vue'
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
}

const props = defineProps<Props>()

// Estado interno
const open = ref(false)
const loading = ref(false)
const error = ref<string | null>(null)
const character = ref<CharacterResource | null>(null)

// Helper: ¿ya tenemos datos?
const hasCharacter = computed(() => character.value !== null)

// Cargar character solo cuando se abre por primera vez
async function ensureCharacterLoaded() {
    if (hasCharacter.value || loading.value) return

    loading.value = true
    error.value = null

    try {
        // Ajusta el tipo de respuesta según tu backend: { data: CharacterResource } o directamente CharacterResource
        const response = await axios.get<Character>(route('ajax.character.info', props.characterId))
        console.log(response.data.character)
        // Si tu API responde como { data: {...} }, cambia a: response.data.data
        character.value = response.data.character
    } catch (e) {
        console.error(e)
        error.value = 'Unable to load character information. Please try again.'
    } finally {
        loading.value = false
    }
}

// Handler cuando cambia el estado de apertura del Dialog
async function handleOpenChange(value: boolean) {
    open.value = value
    if (value) {
        await ensureCharacterLoaded()
    }
}

// Texto de género (mismo mapping que antes, pero encapsulado)
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
                <!-- Fallback trigger (simple texto) -->
                <button type="button" class="text-sm hover:text-muted-foreground cursor-pointer">
                    View character
                </button>
            </slot>
        </DialogTrigger>

        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    <!-- Mientras carga, título genérico -->
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

                    <!-- Character Information -->
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

            <!-- Footer reducido si hay error -->
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
