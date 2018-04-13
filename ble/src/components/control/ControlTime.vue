<template>
  <div id="controlTime">
    <vue-slider v-bind="sliderAttrs" v-model="period"/>
  </div>
</template>

<script>
import { mapMutations } from 'vuex'

import VueSlider from 'vue-slider-component'

export default {
  name: 'ControlTime',
  components: {
    VueSlider,
  },
  data() {
    return {
      sliderAttrs: {
        dotSize: 20,
        tooltip: 'always',
        piecewise: true,
        piecewiseLabel: true,
        processDragable: true,
        style: {
          margin: '0% 20%',
        },
        data: [
          '0',
          '1',
          '2',
          '3',
          '4',
          '5',
          '6',
          '7',
          '8',
          '9',
          '10',
          '11',
          '12',
          '13',
          '14',
          '15',
          '16',
          '17',
          '18',
          '19',
          '20',
          '21',
          '22',
          '23',
        ],
        piecewiseStyle: {
          backgroundColor: '#ccc',
          visibility: 'visible',
          width: '12px',
          height: '12px',
        },
        piecewiseActiveStyle: {
          backgroundColor: '#3498db',
        },
        labelActiveStyle: {
          color: '#3498db',
        },
      },
    }
  },
  computed: {
    period: {
      get() {
        return this.$store.state.period
      },
      set(period) {
        const [start, end] = period
        period = start == end ? [start, String(Number(start) + 1)] : period

        this.$store.commit('setPeriod', { period })
      },
    },
  },
  methods: {
    ...mapMutations(['setPeriod']),
  },
}
</script>

<style lang="scss" scoped>
#controlTime {
  width: 100%;
}
</style>

<style lang="scss">
#controlTime {
}

$color: white;
// $color: rgba(147, 219, 52, 0.68); // green
// $color: rgba(256, 256, 256, 0.8); // white & opacity

.vue-slider-component {
  .vue-slider {
    background-color: rgba(0, 0, 0, 0);
    border: 1px solid white;
  }

  .vue-slider-tooltip {
    border-color: $color;
    background-color: $color;
    color: rgba(0, 0, 0, 0.6);
    font-weight: 700;
    text-shadow: rgba(128, 128, 128, 0.5) 1px 1px 8px;
    font-size: 14px;
  }
  .vue-slider-process {
    background-color: $color;
    &.vue-slider-process-dragable {
      cursor: ew-resize;
    }
  }
  .vue-slider-piecewise-dot {
    background-color: $color !important;
  }
  .vue-slider-piecewise-label {
    color: $color !important;
    font-weight: 700;
    text-shadow: rgba(0, 0, 0, 0.8) 1px 1px 5px;
  }
}
</style>
