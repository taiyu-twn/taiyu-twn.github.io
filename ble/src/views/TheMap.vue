<template>
  <div id="theMap" style="display: unset;">
    <mapbox
      :map-options="{
        // style: 'mapbox://styles/mapbox/dark-v9',
        style: 'mapbox://styles/r04521517/cjf2kwzkl02ni2sn3ak5aoyqs',
        // style: 'mapbox://styles/mapbox/light-v9',
        center: [121.5645897, 25.0332153], // Mapbox Taipei City Label
        // center: [121.5152153, 25.0460988], // Taipei Main Station
        zoom: 12,
      }"
      :geolocate-control="{
        show: true, 
        position: 'top-right'
      }"
      :scale-control="{
        show: true,
        position: 'bottom-left'
      }"
      :fullscreen-control="{
        show: true,
        position: 'top-right'
      }"
      access-token="pk.eyJ1IjoicjA0NTIxNTE3IiwiYSI6ImNqMG96aHFqbzAwZmkyd281aXRidjJkYWkifQ.4Hyl93JpPv19wSSfcVExpQ"
      @map-init="mapInit"
      @map-load="mapLoaded"
    />

    <template v-if="loaded">
      <div class="layers">
        <Layer v-for="(layer, key) in layers" :key="key" :data="layer" :layer-key="key"/>
      </div>
      <div class="routing">
        <Layer v-for="(layer, key) in routingLayers" :key="key" :data="layer" :layer-key="key"/>
      </div>
    </template>

    <ControlTime/>
    <ControlRouting/>

  </div>
</template>

<script>
import Mapbox from 'mapbox-gl-vue'
import { mapState } from 'vuex'

import Layer from '@/components/map/MapLayer'

import ControlTime from '@/components/control/ControlTime'
import ControlRouting from '@/components/control/ControlRouting'

export default {
  name: 'TheMap',
  components: {
    Mapbox,
    Layer,
    ControlTime,
    ControlRouting,
  },
  data() {
    return {
      loaded: false,
      interval: {},
      map: {},
      showed: {},
      nextVisibleLayer: '',
      enableCarousel: true,
    }
  },
  computed: {
    ...mapState(['layers', 'routing', 'period']),
    routingLayers() {
      let layers = {}
      const [start, end] = this.period
      // console.log({ start, end })

      Object.entries(this.routing).map(([key, routing]) => {
        const features = Object.values(routing)
          .filter(f => f.departure_hour >= start && f.departure_hour < end)
          .map(r => ({
            type: 'Feature',
            geometry: r.routing,
          }))

        const layer = {
          name: routing.name,
          color: routing.color,
          mapType: 'line',
          type: 'FeatureCollection',
          show: this.enableCarousel
            ? key == this.nextVisibleLayer
            : routing.show,
          features,
        }

        layers[key] = layer
      })

      return layers
    },
    visibleLayerKeys() {
      return Object.entries(this.routing).filter(([, val]) => val.show)
    },
  },
  mounted() {
    if (this.enableCarousel) this.setCarousel(true)
  },
  beforeDestroy() {
    this.setCarousel(false)
  },
  methods: {
    mapInit(map) {
      console.log('mapInit()', map)
      this.map = map
    },
    mapLoaded(map) {
      console.log('mapLoaded()', map)
      this.loaded = true
    },
    setCarousel(set) {
      if (set) {
        this.interval = clearInterval(this.interval)
        this.interval = setInterval(() => {
          let nextVisibleLayer = Object.entries(this.routing).find(
            ([key, val]) => val.show && !this.showed[key]
          )

          if (nextVisibleLayer) {
            this.nextVisibleLayer = nextVisibleLayer[0] // [key, value]
          } else {
            this.showed = {}
            this.nextVisibleLayer = this.visibleLayerKeys[0]
              ? this.visibleLayerKeys[0][0] // [[key, value]]
              : null
          }
          this.showed[this.nextVisibleLayer] = true
          // console.log('routing show interval: ', this.nextVisibleLayer)
        }, 2000)
      } else {
        this.interval = clearInterval(this.interval)
      }
    },
  },
}
</script>

<style lang="scss" scoped>
#theMap {
  width: 100%;
  height: 100%;
}
#map {
  width: 100%;
  height: 100%;
}

#controlTime {
  position: fixed;
  bottom: 85px;
}

#controlRouting {
  position: fixed;
  // top: 105px;
  // top: 165px;
  top: 155px;
}
</style>
