<template>
  <div class="layer">
    <span :style="{borderColor: data.color, backgroundColor: data.color}" class="circle" @click="toggleShow"/>
    <span :style="{opacity: data.show ? 1 : 0.6}" class="name" >
      <span class="zh">{{ data.name.zh }}</span>
      <br>
      <span class="en">{{ data.name.en }}</span>
    </span>
    <div v-if="hasColorPicker" class="color-picker">
      <Swatches v-model="color"
                :exceptions="colorExceptions"
                :trigger-style="{ width: '20px' , height: '20px' , borderRadius: '3px' }"
                :close-on-select="false"
                colors="material-basic"
                exception-mode="hidden"
                row-length="8"
                show-fallback
      />
    </div>
  </div>
</template>

<script>
import { mapMutations } from 'vuex'

import Swatches from 'vue-swatches'

export default {
  name: 'SidebarLayer',
  components: { Swatches },
  props: {
    layerKey: {
      type: String,
      required: true,
    },
    data: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      colorExceptions: ['#607D8B', '#9E9E9E', '#795548'],
    }
  },
  computed: {
    hasColorPicker() {
      return ['line', 'circle'].find(f => f == this.data.mapType) ? true : false
    },
    color: {
      get() {
        return this.$store.state.layers[this.layerKey].color
      },
      set(color) {
        this.$store.commit('setLayerColor', {
          layerKey: this.layerKey,
          color,
        })
      },
    },
  },
  methods: {
    ...mapMutations(['setLayerShow', 'setLayerColor']),
    toggleShow() {
      this.setLayerShow({
        layerKey: this.layerKey,
        show: !this.data.show,
      })
    },
  },
}
</script>

<style lang="scss" scoped>
.layer {
  // white-space: nowrap;
  display: flex;
  align-items: center;
}

$c: 30px;
.circle {
  display: inline-block;
  border-radius: $c;
  border: solid 3px #f00;
  width: $c;
  height: $c;
  margin: 20px 15px;
  cursor: pointer;
}

.name {
  color: white;
  width: max-content;
  margin-left: 15px;
  margin-top: 3px;

  > span {
    font-weight: 700;
    text-shadow: 0px 0px 3px black;
  }

  .zh {
    letter-spacing: 2px;
  }
  .en {
    font-family: 'Times New Roman', Times, serif;
  }
}

.color-picker {
  margin-top: -10px;
  margin-left: 10px;
  cursor: context-menu;
}
</style>
