<script setup lang="ts">
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination"
import { defineProps, defineEmits } from "vue"

const props = defineProps<{
  currentPage: number,
  totalItems: number,
  itemsPerPage: number,
}>()

const emits = defineEmits<{
  (e: "page-change", page: number): void
}>()

function onPageChange(page: number) {
  emits("page-change", page)
}
</script>

<template>
  <Pagination
    v-slot="{ page }"
    :items-per-page="props.itemsPerPage"
    :total="props.totalItems"
    :default-page="props.currentPage"
  >
    <PaginationContent v-slot="{ items }">
      <PaginationPrevious @click="onPageChange(page - 1)" />

      <template v-for="(item, index) in items" :key="index">
        <PaginationItem
          v-if="item.type === 'page'"
          :value="item.value"
          :is-active="item.value === page"
          @click="onPageChange(item.value)"
        >
          {{ item.value }}
        </PaginationItem>
      </template>

      <PaginationEllipsis :index="4" />

      <PaginationNext @click="onPageChange(page + 1)" />
    </PaginationContent>
  </Pagination>
</template>
