<script setup>
import { computed } from 'vue';

  const props = defineProps({
    label: {
      type: String,
      required: true
    },
    content: {
      type: [String, Number, Boolean],
      default: ''
    },
    showBorder: {
      type: Boolean,
      default: true
    }
  })
  
  const borderClass = computed(() => {
    return props.showBorder 
      ? 'border-b border-gray-100 dark:border-white/10' 
      : ''
  })
</script>

<template>
    <div class="px-4 py-6 sm:px-0" :class="borderClass">
      <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">
        {{ label }}
      </dt>
      <dd class="mt-1 text-sm text-gray-700 dark:text-gray-400">
        <slot>
          {{ content }}
        </slot>
      </dd>
    </div>
  </template>
  