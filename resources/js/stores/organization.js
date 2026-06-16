import { defineStore } from "pinia";
import { ref } from "vue";
import { useApi } from "@/composables/useApi";

export const useOrganizationStore = defineStore("organization", () => {
    const { get } = useApi();

    const isEmpty = ref(true);

    const name = ref("");
    const rating = ref(0.0);
    const ratingCount = ref(0);
    const reviewCount = ref(0);

    const reviews = ref([]);

    const fetch = async (url) => {
        try {
            reset();

            const data = await get("/api/organizations", {
                params: { link: url },
            });

            name.value = data.data.data.name;
            rating.value = data.data.data.rating;
            ratingCount.value = data.data.data.ratingCount;
            reviewCount.value = data.data.data.reviewCount;
            reviews.value = data.data.data.reviews;

            isEmpty.value = false;
        } catch (error) {
            console.error(error.message);
        }
    };

    const reset = () => {
        isEmpty.value = true;

        name.value = "";
        name.rating = 0.0;
        name.ratingCount = 0;
        name.reviewCount = 0;

        name.reviews = [];
    };

    return {
        isEmpty,
        name,
        rating,
        ratingCount,
        reviewCount,
        reviews,
        fetch,
    };
});
