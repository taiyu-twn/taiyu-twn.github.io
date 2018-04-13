<template>
  <div id="app">
    <TheMap/>
    <TheCover/>
    <TheSidebar/>
  </div>
</template>

<script>
import TheMap from '@/views/TheMap'
import TheCover from '@/views/TheCover'
import TheSidebar from '@/views/TheSidebar'

export default {
  name: 'App',
  components: { TheMap, TheCover, TheSidebar },
  data() {
    return {}
  },
}
</script>

<style lang="scss">
body {
  margin: 0px;
}

#app {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
  width: 100vw;
  height: 100vh;

  display: flex;

  * {
    // font-family: 'Roboto', 'Avenir', Helvetica, Arial, sans-serif, 'FontAwesome';

    // font-weight: 300;
  }
}
</style>
