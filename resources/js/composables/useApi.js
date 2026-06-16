import { ref } from "vue";
import { Notify } from "quasar";
import { api } from "@/api/api";

export function useApi() {
    const loading = ref(false);
    const error = ref(null);

    const request = async (method, url, data = null, config = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.request({
                method,
                url,
                data,
                ...config,
            });
            return response;
        } catch (err) {
            let message = err.message;

            if (err.response) {
                const status = err.response.status;
                switch (status) {
                    case 429:
                        message = "Слишком много попыток. Попробуйте позже";
                        break;
                    case 401:
                        message = "Неверный логин или пароль";
                        break;
                    case 419:
                        message =
                            "Сессия истекла. Пожалуйста, обновите страницу";
                        break;
                    default:
                        message =
                            err.response?.data?.message ||
                            err.response?.data ||
                            `Ошибка ${status}`;
                }
            } else if (err.request) {
                message = "Нет соединения с сервером. Проверьте интернет.";
            } else {
                message = err.message;
            }

            error.value = message;
            Notify.create({
                type: "negative",
                message: message,
                timeout: 5000,
            });
            throw new Error(message);
        } finally {
            loading.value = false;
        }
    };

    const get = (url, config) => request("GET", url, null, config);
    const post = (url, data, config) => request("POST", url, data, config);
    const put = (url, data, config) => request("PUT", url, data, config);
    const patch = (url, data, config) => request("PATCH", url, data, config);
    const del = (url, config) => request("DELETE", url, null, config);

    return {
        loading,
        error,
        get,
        post,
        put,
        patch,
        del,
    };
}
