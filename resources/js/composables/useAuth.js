import { useApi } from "@/composables/useApi";

export function useAuth() {
    const { post, get } = useApi();

    const getCsrfToken = async () => {
        try {
            await get("/sanctum/csrf-cookie");
        } catch (error) {
            throw new Error(error.message);
        }
    };

    const login = async (email, password) => {
        const formData = new FormData();
        formData.append("email", email);
        formData.append("password", password);

        try {
            await post("/api/login", formData, {
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "multipart/form-data",
                },
            });
        } catch (error) {
            throw new Error(error.message);
        }
    };

    return {
        login,
        getCsrfToken,
    };
}
