<template>
    <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { getCsrfToken } = useAuth()

onMounted(() => {
    getCsrfToken().then(() => console.log('CSRF токен получен'))
})
</script>
