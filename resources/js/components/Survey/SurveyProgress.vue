<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { type SurveyResource } from '@/types/global'; // Asumiendo que SurveyResource tiene progress_percentage, total_votes, total_combinations_expected, etc.

interface Props {
    survey: SurveyResource; // El objeto survey que contiene la información de progreso
}

defineProps<Props>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Progress</CardTitle>
            <CardDescription>Survey completion status</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="flex items-center gap-4">
                <div class="w-full">
                    <Progress :value="survey.progress_percentage" :max="100" />
                </div>
                <span class="text-sm font-medium"
                    >{{ survey.progress_percentage?.toFixed(2) ?? 0 }}%</span
                >
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                <div>
                    <p class="font-semibold">{{ survey.total_votes ?? 0 }}</p>
                    <p class="text-muted-foreground">Voted</p>
                </div>
                <div>
                    <p class="font-semibold">
                        {{
                            (survey.total_combinations_expected ?? 0) -
                            (survey.total_votes ?? 0)
                        }}
                    </p>
                    <p class="text-muted-foreground">Remaining</p>
                </div>
                <div>
                    <p class="font-semibold">
                        {{ survey.total_combinations_expected ?? 0 }}
                    </p>
                    <p class="text-muted-foreground">Total</p>
                </div>
            </div>
        </CardContent>
        <CardFooter v-if="survey.is_completed" class="text-xs text-green-600">
            Survey completed!
        </CardFooter>
    </Card>
</template>

<style scoped>
/* Estilos específicos si es necesario */
</style>
