<template>
    <q-drawer v-model="drawer" show-if-above :width="400" :breakpoint="500" bordered side="right" class="bg-grey-4">
        <div v-if="organization && !organization?.isEmpty" class="q-pa-lg">
            <div class="text-h4">{{ organization.name }}</div>

            <div class="q-mb-md row q-gutter-x-sm align-center">
                <q-rating v-model="organization.rating" size="1.5em" icon="star_border" icon-selected="star"
                    icon-half="star_half" readonly />
                    <span>({{ organization.rating }})</span>
            </div>

            <div class="text-caption row">
                <span>Оценок </span>&nbsp;
                <span class="text-bold">{{ organization.ratingCount }}</span>
            </div>

            <div class="text-caption row">
                <span>Отзывов </span>&nbsp;
                <span class="text-bold">{{ organization.reviewCount }}</span>
            </div>
        </div>
    </q-drawer>
</template>

<script setup>
import { useOrganizationStore } from "@/stores/organization";
import { ref } from "vue";

const organization = useOrganizationStore();

const drawer = ref(true);
</script>