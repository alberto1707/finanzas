<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card class="elevation-12">
          <v-toolbar color="primary" dark flat>
            <v-toolbar-title>Registro</v-toolbar-title>
          </v-toolbar>
          <v-card-text>
            <v-form ref="form" v-model="valid">
              <v-text-field
                v-model="name"
                label="Nombre"
                :rules="[v => !!v || 'Nombre es requerido']"
              ></v-text-field>

              <v-text-field
                v-model="email"
                label="Email"
                type="email"
                :rules="[v => !!v || 'Email es requerido']"
              ></v-text-field>

              <v-text-field
                v-model="password"
                label="Password"
                type="password"
                :rules="[v => !!v || 'Password es requerido']"
              ></v-text-field>

               <v-text-field
                v-model="password_confirmation"
                label="Confirmar Password"
                type="password"
                :rules="[v => !!v || 'Confirmación requerida', v => v === password || 'Passwords no coinciden']"
              ></v-text-field>

              <v-alert v-if="error" type="error" dense>{{ error }}</v-alert>
            </v-form>
          </v-card-text>
          <v-card-actions>
            <v-btn variant="text" color="secondary" to="/login">Ya tengo cuenta</v-btn>
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="register" :loading="loading">Registrarse</v-btn>
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
const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const error = ref('');
const loading = ref(false);
const valid = ref(false);

const register = async () => {
    if (!valid.value) return;

    loading.value = true;
    error.value = '';

    try {
        const response = await axios.post('/api/register', {
            name: name.value,
            email: email.value,
            password: password.value,
            password_confirmation: password_confirmation.value
        });

        const token = response.data.access_token;
        const user = response.data.user;

        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));

        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

        router.push('/');
    } catch (e) {
        if (e.response && e.response.data && e.response.data.message) {
            error.value = e.response.data.message;
        } else {
            error.value = 'Error al registrarse';
        }
    } finally {
        loading.value = false;
    }
};
</script>
