import './bootstrap';
import { createApp } from 'vue';
import ChaptersFilter from './components/ChaptersFilter.vue';

const app = createApp({});
app.component('chapters-filter', ChaptersFilter);

app.mount('#app');