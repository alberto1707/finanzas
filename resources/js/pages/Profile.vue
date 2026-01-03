<template>
    <v-container>
        <v-row>
            <v-col cols="12">
                <h2 class="text-h5 mb-4">Mi Perfil</h2>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" md="6">
                <v-card class="mb-4">
                    <v-card-title>Información Personal</v-card-title>
                    <v-card-text>
                        <v-form @submit.prevent="updateProfile">
                            <v-text-field
                                v-model="formProfile.name"
                                label="Nombre Completo"
                                variant="outlined"
                                class="mb-2"
                            ></v-text-field>
                            <v-text-field
                                v-model="formProfile.email"
                                label="Correo Electrónico"
                                variant="outlined"
                                disabled
                                hint="El correo no se puede cambiar"
                                persistent-hint
                            ></v-text-field>
                            <v-btn
                                color="primary"
                                type="submit"
                                :loading="loadingProfile"
                                class="mt-4"
                            >
                                Guardar Cambios
                            </v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title>Cambiar Contraseña</v-card-title>
                    <v-card-text>
                        <v-form @submit.prevent="changePassword">
                            <v-text-field
                                v-model="formPassword.current_password"
                                label="Contraseña Actual"
                                type="password"
                                variant="outlined"
                                class="mb-2"
                            ></v-text-field>
                            <v-text-field
                                v-model="formPassword.password"
                                label="Nueva Contraseña"
                                type="password"
                                variant="outlined"
                                class="mb-2"
                            ></v-text-field>
                            <v-text-field
                                v-model="formPassword.password_confirmation"
                                label="Confirmar Nueva Contraseña"
                                type="password"
                                variant="outlined"
                            ></v-text-field>
                            <v-btn
                                color="warning"
                                type="submit"
                                :loading="loadingPassword"
                                class="mt-4"
                            >
                                Actualizar Contraseña
                            </v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-snackbar v-model="snackbar" :color="snackbarColor">
            {{ snackbarText }}
            <template v-slot:actions>
                <v-btn variant="text" @click="snackbar = false">Cerrar</v-btn>
            </template>
        </v-snackbar>
    </v-container>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const user = ref(null);
const loadingProfile = ref(false);
const loadingPassword = ref(false);

const formProfile = ref({
    name: "",
    email: "",
});

const formPassword = ref({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const snackbar = ref(false);
const snackbarText = ref("");
const snackbarColor = ref("success");

const showMessage = (text, color = "success") => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

onMounted(() => {
    const userData = localStorage.getItem("user");
    if (userData) {
        user.value = JSON.parse(userData);
        formProfile.value.name = user.value.name;
        formProfile.value.email = user.value.email;
    }
});

const updateProfile = async () => {
    loadingProfile.value = true;
    try {
        const response = await axios.put("/api/profile", {
            name: formProfile.value.name,
        });
        localStorage.setItem("user", JSON.stringify(response.data.user)); // Update local storage
        showMessage("Perfil actualizado correctamente");
        // Optional: Emit event to update parent layout if needed, requires global state or event bus.
        // For simplicity, a reload would work, but reactive state is better.
        // We might simply reload the page or update global state if we had Pinia.
        setTimeout(() => location.reload(), 1000);
    } catch (e) {
        console.error(e);
        showMessage("Error al actualizar perfil", "error");
    } finally {
        loadingProfile.value = false;
    }
};

const changePassword = async () => {
    loadingPassword.value = true;
    try {
        await axios.put("/api/password", formPassword.value);
        showMessage("Contraseña actualizada. Inicia sesión de nuevo.");
        formPassword.value = {
            current_password: "",
            password: "",
            password_confirmation: "",
        };
    } catch (e) {
        console.error(e);
        const msg = e.response?.data?.message || "Error al cambiar contraseña";
        showMessage(msg, "error");
    } finally {
        loadingPassword.value = false;
    }
};
</script>
