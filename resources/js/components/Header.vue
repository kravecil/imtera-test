<template>
    <q-header bordered>
        <q-toolbar class="q-pa-sm">
            <q-toolbar-title>
                <q-input dark outlined dense label-color="white" v-model="urlField"
                    :rules="[val => !!val || 'Обязательное поле', val => !!val && linkPattern.test(val) || 'Некорректная ссылка на организацию на Яндекс.Картах']"
                    lazy-rules label="Укажите ссылку организации">
                    <template v-slot:prepend>
                        <q-icon name="search" />
                    </template>
                    <template v-slot:append>
                        <q-btn dense flat label="Искать" no-caps @click="onFetchOrganization" />
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
</template>

<script setup>
import { useAuth } from "@/composables/useAuth";
import { useAuthStore } from "@/stores/auth";
import { useOrganizationStore } from "@/stores/organization";
import { Loading } from "quasar";
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";

const auth = useAuthStore();
const organization = useOrganizationStore();

const { getUser, logout } = useAuth();
const router = useRouter()

const urlField = ref('')

const linkPattern = /^https?:\/\/yandex\.ru\/maps\/org\/([a-zA-Z0-9._-]+)\/(\d+)(?:\/|\?|#|$)/

onMounted(async () => {
    auth.loginUser(await getUser())
})

const onLogout = async () => {
    await logout()
    router.push('/login')
}

const onFetchOrganization = async () => {
    try {
        Loading.show({ message: "Ссылка обрабатывается..." })

        await organization.fetch(urlField.value)
    } catch (error) {
        console.error(error.message)
    } finally {
        Loading.hide()
    }
}
</script>