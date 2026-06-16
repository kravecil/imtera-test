<template>
    <q-page padding>
        <template v-if="organization">
            <Review v-for="(review, index) in organization.reviews" :key="index" :review="review" class="q-my-md" />
        </template>
    </q-page>
</template>

<script setup>
import { useOrganizationStore } from "@/stores/organization";

import Review from "@/components/Review.vue";

const organization = useOrganizationStore();
</script>