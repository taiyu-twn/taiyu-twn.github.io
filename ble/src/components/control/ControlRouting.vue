<template>
  <div id="controlRouting">
    <el-button-group id="routingSelect">
      <el-button v-for="(r, key) in routing" :key="key" 
                 :class="{active: r.show}"
                 size="small"
                 @click="toggleShow(key)"
                 @mouseenter.self.native="mouseenter(key)" @mouseleave.self.native="mouseleave(key)"
      >
        {{ r.name.en }}
        <span @click.stop="() => {}">
          <transition name="slide-fade">
            <Swatches v-show="ishover"
                      :value="r.color"
                      :exceptions="colorExceptions"
                      :trigger-style="{ width: '20px' , height: '20px' , borderRadius: '3px' }"
                      :close-on-select="false"
                      colors="material-basic"
                      exception-mode="hidden"
                      row-length="8"
                      show-fallback
                      class="color-picker"
                      @input="(color) => {setRoutingColor({ routingKey: key, color })}"
            />
          </transition>
        </span>
      </el-button>
    </el-button-group>
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'

import Swatches from 'vue-swatches'

export default {
  name: 'ControlRouting',
  components: { Swatches },
  data() {
    return {
      colorExceptions: ['#607D8B', '#9E9E9E', '#795548'],
      ishover: false,
    }
  },
  computed: {
    ...mapState(['routing']),
  },
  methods: {
    ...mapMutations(['setRoutingShow', 'setRoutingColor']),
    toggleShow(key) {
      const routingKey = key
      const show = !this.routing[routingKey].show
      this.setRoutingShow({
        routingKey,
        show,
      })
    },
    mouseenter() {
      // console.log('mouseenter', this.ishover)
      this.ishover = true
    },
    mouseleave() {
      // console.log('mouseleave', this.ishover)
      this.ishover = false
    },
  },
}
</script>

<style lang="scss" scoped>
#controlRouting {
  width: 100%;
  text-align: center;
}

#routingSelect {
  $color: gray;

  .el-button {
    // font-weight: 700;
    background-color: transparentize(white, 0.3) !important;
    color: $color !important;
    // border-color: lighten($color, 30%) !important;
    border-width: 0px !important;
    min-width: 150px;

    &:hover {
      color: darken($color, 10%) !important;
      background-color: transparentize(white, 0.25) !important;
      // border-color: darken($color, 50%) !important;
    }

    &:active,
    &.active {
      color: darken($color, 100%) !important;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
  }
}

.color-picker {
  position: absolute;
  margin: 20px calc(50% - 25px);

  /deep/ .vue-swatches__wrapper {
    overflow: auto;
  }
}

.slide-fade-enter-active {
  transition: all 0.3s ease;
}
.slide-fade-leave-active {
  transition: all 0.4s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter,
.slide-fade-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}
</style>
