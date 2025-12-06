<script setup lang="ts">
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

interface Props {
    character: CharacterResource
}

const props = defineProps<Props>()
</script>

<template>
    <Dialog>
        <!-- Trigger: slot personalizable + fallback -->
        <DialogTrigger asChild>
            <!--
        Si el padre define <template #trigger>... se usa ese contenido.
        Si no define nada, se usa el trigger por defecto (avatar + nombre).
      -->
            <slot name="trigger">
                <!-- Trigger por defecto -->
                <div class="flex items-center gap-3 rounded-lg border p-3 hover:bg-accent cursor-pointer">
                    <div class="relative aspect-square w-12 overflow-hidden rounded-full border">
                        <img v-if="character.picture_url" :src="character.picture_url" :alt="character.fullname"
                            class="h-full w-full object-cover" />
                        <div v-else class="flex h-full w-full items-center justify-center bg-muted">
                            <span class="text-xs text-muted-foreground">No img</span>
                        </div>
                    </div>

                    <div class="flex-1 truncate">
                        <p class="truncate text-sm font-medium">
                            {{ character.fullname }}
                        </p>
                        <p v-if="character.nickname" class="truncate text-xs text-muted-foreground">
                            {{ character.nickname }}
                        </p>
                    </div>
                </div>
            </slot>
        </DialogTrigger>

        <!-- Dialog Content -->
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ character.fullname }}</DialogTitle>
                <DialogDescription>
                    {{ character.nickname }}
                </DialogDescription>
            </DialogHeader>

            <!-- Body -->
            <div class="flex flex-col text-sm gap-4 items-center">
                <img :src="character.picture_url" :alt="character.fullname"
                    class="h-64 w-64 rounded-full object-cover" />

                <div>
                    <p class="text-sm text-muted-foreground line-clamp-3 mb-4">
                        {{ character.bio }}
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
                                    {{
                                        character.gender === 0
                                            ? 'Other'
                                            : character.gender === 1
                                                ? 'Male'
                                                : character.gender === 2
                                                    ? 'Female'
                                                    : character.gender === 3
                                    ? 'Non-binary'
                                    : 'Unknown'
                                    }}
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
                                    {{ character.dob_formatted || 'N/A' }}
                                    <span class="text-xs text-muted-foreground/70">
                                        ({{ character.dob_for_humans || 'N/A' }})
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
                                {{ character.status === true ? 'Active' : 'Inactive' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <DialogFooter>
                <Link :href="route('public.characters.show', character.id)">
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
        </DialogContent>
    </Dialog>
</template>
