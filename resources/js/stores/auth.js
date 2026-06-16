import { defineStore } from "pinia";
import { ref } from "vue";

export const useAuthStore = defineStore("auth", () => {
    const user = ref(null);

    const loginUser = (data) => (user.value = data);
    const logoutUser = () => (user.value = null);

    return {
        user,
        loginUser,
        logoutUser,
    };
});
