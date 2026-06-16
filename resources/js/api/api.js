import axios from "axios";

const baseURL = import.meta.env.VITE_API_BASE_URL;

const api = axios.create({
    baseURL,
    withCredentials: true,
    withXSRFToken: true,
});

api.interceptors.request.use(config => {
    return config;
});

api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            // location.reload();
        }
        return Promise.reject(error);
    }
);


export { api };
