<template>
    <q-input v-model="text" label="Standard" />
    <q-btn color="primary" label="Primary" />
</template>

<script setup>
import { ref } from 'vue'
const text = ref('')
</script>