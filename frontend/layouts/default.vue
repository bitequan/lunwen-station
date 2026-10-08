<template>
  <div class="layout-default">
    <Navbar @login="openLogin" />
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
const login = useLoginModal()

const openLogin = () => {
  login.open()
}
</script>

<style scoped>
.layout-default {
  min-height: 100vh;
}
</style>
