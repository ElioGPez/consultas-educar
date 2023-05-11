<template>
    <div>
        Bienvenido {{ name }}
    </div>
</template>

<script>

export default {
    name: "Dashboard",
    data() {
        return {
            name: null,
        }
    },
    created() {
        if (window.Laravel.user) {
            this.name = window.Laravel.user.name

        this.$axios.get('/sanctum/csrf-cookie').then(response => {
            this.$axios.get('/api/publicaciones/all')
                .then(response => {
                    console.log(response.data);
                })
                .catch(function (error) {
                    console.error(error);
                });
        })
        }
        
    },
    beforeRouteEnter(to, from, next) {
        if (!window.Laravel.isLoggedin) {
            window.location.href = "/";
        }
        next();
    }
}
</script>