import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.component('QuillEditor', QuillEditor);
app.mount('#app');
