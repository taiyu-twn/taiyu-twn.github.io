<template>
  <div id="theSidebar" @mouseover="mouseover" @mouseout="mouseout">
    <transition name="slide-fade">
      <div v-show="ishover" class="layers" >
        <Layer v-for="(layer, key) in layers" :key="key" :data="layer" :layer-key="key"/>
      </div>
    </transition>
  </div>
</template>

<script>
import { mapState } from 'vuex'

import Layer from '@/components/sidebar/SidebarLayer'

export default {
  name: 'TheSidebar',
  components: { Layer },
  data() {
    return {
      ishover: false,
    }
  },
  computed: {
    ...mapState(['layers']),
  },
  methods: {
    mouseover() {
      this.ishover = true
    },
    mouseout() {
      this.ishover = false
    },
  },
}
</script>

<style lang="scss" scoped>
#theSidebar {
  width: 70px;
  height: 100%;
  position: fixed;
  z-index: 100;
  background-color: hsla(0, 0%, 0%, 0.6);
  transition: transform 0.8s ease;
  transform: translateX(-68px);

  &:hover {
    transform: translateX(0px);
  }
}

.layers {
  position: absolute;
  top: 200px;
}

.slide-fade-enter-active {
  transition: all 0.3s ease;
}
.slide-fade-leave-active {
  transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter,
.slide-fade-leave-to {
  transform: translateX(-10px);
  opacity: 0;
}
</style>
