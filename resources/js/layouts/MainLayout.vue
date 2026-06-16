<template>
    <q-layout view="lhh LpR lff">
        <q-header elevated>
            <q-toolbar class="q-pa-sm">
                <q-toolbar-title>
                    <q-input dark outlined dense label-color="white" v-model="searchField"
                        :rules="[val => !!val || 'Обязательное поле', val => !!val && linkPattern.test(val) || 'Некорректная ссылка на организацию на Яндекс.Картах']"
                        lazy-rules label="Укажите ссылку организации">
                        <template v-slot:prepend>
                            <q-icon name="search" />
                        </template>
                        <template v-slot:append>
                            <q-btn dense flat label="Искать" no-caps />
                        </template>
                    </q-input>
                </q-toolbar-title>
                <div v-if="auth.user" class="q-px-md">
                    <div class="text-bold">{{ auth.user.name }}</div>
                    <div class="text-caption">{{ auth.user.email }}</div>
                </div>
                <q-btn flat @click="onLogout" icon="logout" />
            </q-toolbar>
        </q-header>
        <q-page-container>
            <router-view />
        </q-page-container>
    </q-layout>
</template>

<script setup>
import { useAuthStore } from "@/stores/auth";
import { onMounted } from "vue";
import { useAuth } from "@/composables/useAuth";
import { ref } from "vue";
import { useRouter } from "vue-router";

const auth = useAuthStore();
const { getUser, logout } = useAuth();
const router = useRouter()

const linkPattern = /^https?:\/\/yandex\.ru\/maps\/org\/([a-zA-Z0-9._-]+)\/(\d+)(?:\/|\?|#|$)/

const searchField = ref('')

onMounted(async () => {
    auth.loginUser(await getUser())
})

const onLogout = async () => {
    await logout()
    router.push('/login')
}
</script>