<template>
    <div>
        <h2>Preferencias</h2>
        <hr>
        <select v-model="nivel" class="form-select" aria-label="Default select example">
            <option selected value="0">Elija el Nivel...</option>
            <option v-for="(nivel,i) in niveles" :key="i" :value="nivel.id">{{ nivel.nombre }}</option>
        </select>
    </div>
</template>

<script>
export default {
    name: "cargar-preferencias",
    data() {
        return {
            name: null,
            niveles: [],
            nivel: 0,
        }
    },
    created() {
        if (window.Laravel.user) {
            this.name = window.Laravel.user.name

        this.$axios.get('/sanctum/csrf-cookie').then(response => {
            this.$axios.get('/api/publicaciones/getNivel')
                .then(response => {
                    console.log(response.data);
                    this.niveles = response.data;
                })
                .catch(function (error) {
                    console.error(error);
                });
        })
        }
        
    },
}
</script>