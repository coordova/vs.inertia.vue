<!-- components/ui/DatePicker.vue -->
<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { CalendarIcon } from 'lucide-vue-next';
import { Calendar } from '@/components/ui/calendar';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { DateFormatter, type DateValue, getLocalTimeZone, parseDate } from '@internationalized/date';

const df = new DateFormatter('en-US', { dateStyle: 'long' });

// Entrada / salida: string ISO "YYYY-MM-DD"
const props = defineProps<{
  modelValue: string | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string | null];
}>();

// Convertimos el string ISO a DateValue para el Calendar
const date = ref<DateValue | null>(
  props.modelValue ? parseDate(props.modelValue) : null
);

const isOpen = ref(false);

// Cada vez que cambia el calendario, emitimos string ISO
watch(date, (newVal) => {
  emit('update:modelValue', newVal ? newVal.toString() : null);
  isOpen.value = false;
});

</script>

<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="
          cn(
            'w-[280px] justify-start text-left font-normal',
            !date && 'text-muted-foreground'
          )
        "
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ date ? df.format(date.toDate(getLocalTimeZone())) : 'Pick a date' }}
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-0">
      <Calendar v-model="date" initial-focus />
    </PopoverContent>
  </Popover>
</template>