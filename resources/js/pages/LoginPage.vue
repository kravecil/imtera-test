<template>
    <q-form @submit="onSubmit">
        <q-page padding class="flex flex-center bg-blue-1">
            <q-card class="card relative-position">
                <q-card-section class="q-pa-lg bg-primary text-white">
                    <div class="text-h6">Парсинг отзывов Яндекс.Карт</div>
                    <div class="text-subtitle2">Выполните вход в систему</div>
                </q-card-section>

                <q-card-section class="q-pa-lg">
                    <q-input v-model="email" label="EMail" />
                    <q-input v-model="password" label="Пароль" type="password" />
                </q-card-section>

                <q-card-actions vertical class="q-pa-lg">
                    <q-btn color="primary" type="submit">Войти</q-btn>
                </q-card-actions>

                <q-inner-loading :showing="loading" label="Аутентификация..." />
            </q-card>
        </q-page>
    </q-form>
</template>

<script setup>
import { ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { Notify } from 'quasar'
import { useRouter } from 'vue-router'

const { login } = useAuth()
const router = useRouter()

const email = ref('')
const password = ref('')

const loading = ref(false)

const onSubmit = async () => {
    try {
        loading.value = true
        await login(email.value, password.value)
        router.push('/')
    } catch (error) {
        console.error(error)
        Notify.create({
            message: error.message,
            color: 'negative'
        })
    } finally {
        loading.value = false
    }
}
</script>

<style scoped lang="sass">
.card
    width: 100%
    max-width: 350px
</style>