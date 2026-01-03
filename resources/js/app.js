import './bootstrap';
import { createApp } from 'vue';
import { createVuetify } from 'vuetify';
import App from './App.vue';
import router from './router';
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';

import axios from 'axios';

const vuetify = createVuetify();

const token = localStorage.getItem('token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

createApp(App).use(vuetify).use(router).mount('#app');
