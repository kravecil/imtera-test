import { createApp } from "vue";

import { pinia } from "@/plugins/pinia";
import { Quasar, quasarOptions } from "@/plugins/quasar";
import { router } from "@/plugins/router";

import App from "@/App.vue";

const app = createApp(App);

app.use(router);
app.use(pinia);
app.use(Quasar, quasarOptions);

app.mount("#app");
