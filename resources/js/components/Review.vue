<template>
    <q-item class="rounded-borders bg-green-2 q-pa-lg">
        <q-item-section>
            <q-item-label overline>{{ review.date }}</q-item-label>
            <q-item-label class="text-bold text-h6">{{ review.author }}</q-item-label>
            <q-item-label caption>Оценка {{ review.rating }}</q-item-label>
            <q-separator spaced />
            <q-item-label class="text">{{ review.text }}</q-item-label>
        </q-item-section>
    </q-item>
</template>

<script setup>
const props = defineProps(['review'])
</script>

<style scoped lang="sass">
.text
    white-space: pre-wrap
</style>