import { Quasar, Notify, Loading } from "quasar";
import quasarLang from "quasar/lang/ru";

import "@quasar/extras/material-icons/material-icons.css";

import "quasar/src/css/index.sass";

export const quasarOptions = {
    plugins: { Notify, Loading },
    lang: quasarLang,
};

export { Quasar };
