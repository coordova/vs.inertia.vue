<script setup lang="ts">
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination"
import { defineProps, defineEmits, computed } from "vue"

const props = defineProps<{
  currentPage: number,
  totalItems: number,
  itemsPerPage: number,
}>()

const emits = defineEmits<{
  (e: "page-change", page: number): void
}>()

const totalPages = computed(() =>
  Math.ceil(props.totalItems / props.itemsPerPage)
);

function onPageChange(page: number) {
  // Si ya estás en la página, no avanzar
  if (page === props.currentPage || page < 1 || page > totalPages.value) return;
  emits("page-change", page)
}
</script>

<template>
  <Pagination
    v-slot="{ page }"
    :items-per-page="itemsPerPage"
    :total="totalItems"
    :default-page="currentPage"
  >
    <PaginationContent v-slot="{ items }">
      <PaginationPrevious
        :aria-disabled="props.currentPage === 1"
        :tabindex="props.currentPage === 1 ? -1 : 0"
        :class="props.currentPage === 1 ? 'pointer-events-none opacity-50' : ''"
        @click="onPageChange(props.currentPage - 1)"
      />

      <template v-for="(item, index) in items" :key="index">
        <PaginationItem
          v-if="item.type === 'page'"
          :value="item.value"
          :is-active="item.value === props.currentPage"
          :aria-disabled="item.value === props.currentPage"
          :tabindex="item.value === props.currentPage ? -1 : 0"
          :class="item.value === props.currentPage ? 'pointer-events-none opacity-50' : ''"
          @click="onPageChange(item.value)"
        >
          {{ item.value }}
        </PaginationItem>
      </template>

      <PaginationEllipsis :index="4" />

      <PaginationNext
        :aria-disabled="props.currentPage === totalPages"
        :tabindex="props.currentPage === totalPages ? -1 : 0"
        :class="props.currentPage === totalPages ? 'pointer-events-none opacity-50' : ''"
        @click="onPageChange(props.currentPage + 1)"
      />
    </PaginationContent>
  </Pagination>
</template>
