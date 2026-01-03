<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>Ingreso Finanzas</v-toolbar-title>
          </v-toolbar>
          <v-card-text>
            <v-form ref="form" v-model="valid">
              <v-text-field
                v-model="email"
                label="Email"
                name="email"
                prepend-icon="mdi-account"
                type="email"
                :rules="[v => !!v || 'Email es requerido']"
              ></v-text-field>

              <v-text-field
                v-model="password"
                id="password"
                label="Password"
                name="password"
                prepend-icon="mdi-lock"
                type="password"
                :rules="[v => !!v || 'Password es requerido']"
              ></v-text-field>

              <v-checkbox
                v-model="rememberMe"
                label="Mantener sesión iniciada"
              ></v-checkbox>

              <v-alert v-if="error" type="error" dense>{{ error }}</v-alert>
            </v-form>
          </v-card-text>
          <v-card-actions>
            <v-btn text color="secondary" :to="{ name: 'Register' }">Registrarse</v-btn>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="login" :loading="loading">Ingresar</v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const error = ref('');
const loading = ref(false);
const valid = ref(false);

const login = async () => {
    if (!valid.value) return;

    loading.value = true;
    error.value = '';

    try {
        // Laravel CSRF protection needs cookie first?
        // For API (Stateful/Sanctum) usually yes: await axios.get('/sanctum/csrf-cookie');
        // For API (Stateless/Tokens): No need if requests don't use cookies.
        // But our login/register routes are usually open.
        // We configured 'api' middleware to 'throttle:api'.

        const response = await axios.post('/api/login', {
            email: email.value,
            password: password.value
        });

        const token = response.data.access_token;
        const user = response.data.user;

        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));

        // Handle session lifetime logic roughly in frontend storage choice
        // If not rememberMe, we could use sessionStorage instead.
        // But logic in router uses localStorage.
        // We'll stick to localStorage as common practice for JWT-like flows.
        // If strict logout needed, user presses Logout.

        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

        router.push('/');
    } catch (e) {
        if (e.response && e.response.data && e.response.data.message) {
            error.value = e.response.data.message;
        } else {
            error.value = 'Error al iniciar sesión';
        }
    } finally {
        loading.value = false;
    }
};
</script>
