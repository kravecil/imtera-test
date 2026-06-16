export default [
    { path: "/", redirect: "/search" },
    {
        path: "/login",
        component: () => import("@/layouts/LoginLayout.vue"),
        children: [
            {
                path: "",
                component: () => import("@/pages/LoginPage.vue"),
            },
        ],
    },
    {
        path: "/search",
        component: () => import("@/layouts/MainLayout.vue"),
        children: [
            {
                path: "",
                component: () => import("@/pages/MainPage.vue"),
            },
        ],
    },
];
