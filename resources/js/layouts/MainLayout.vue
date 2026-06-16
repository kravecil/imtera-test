<template>
    <q-layout view="hHh LpR lff">
        <Header />
        <OrganizationDrawer />
        <q-page-container>
            <router-view />
        </q-page-container>
    </q-layout>
</template>

<script setup>
import Header from "@/components/Header.vue";
import OrganizationDrawer from "@/components/OrganizationDrawer.vue";

</script>