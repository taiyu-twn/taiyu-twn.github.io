<template>
  <div/>
</template>

<script>
const emptyFeature = { type: 'Feature', color: '#888', geometry: null }

export default {
  name: 'MapLayer',
  props: {
    layerKey: {
      type: String,
      required: true,
    },
    data: {
      type: Object,
      required: true,
      default: emptyFeature,
    },
  },
  data() {
    return {
      layerNum: 0,
    }
  },
  computed: {
    map() {
      return this.$parent.map
    },
    id() {
      return `map-layer-${this._uid}`
    },
    show() {
      return this.data.show
    },
  },
  watch: {
    show(show) {
      this.setLayerShow(show)
    },
    data() {
      this.refresh()
    },
  },
  mounted() {
    const name = this.data.name.zh
    const type = this.data.mapType
    const show = this.data.show
    console.log('[MapLayer Mounted]', this.id, { name, type, show })

    this.map.addSource(this.id, {
      type: 'geojson',
      data: this.data,
    })

    if (type == 'line') {
      this.layerNum = 1
      const defaultPaint = {
        'line-color': this.data.color,
        // 'line-width': 8,
        'line-width': {
          base: 1,
          // stops: [[9, 1], [22, 8]],
          stops: [[9, 1], [18, 6], [22, 20]],
        },
        'line-opacity': 0.15,
      }

      this.map.addLayer({
        id: this.id + 1,
        type: 'line',
        source: this.id,
        layout: {
          'line-join': 'round',
          'line-cap': 'round',
        },
        paint: Object.assign(defaultPaint, this.data.paint || {}),
      })
    }

    if (type == 'circle') {
      this.layerNum = 1
      const defaultPaint = {
        // make circles larger as the user zooms from z12 to z22
        'circle-radius': {
          base: 1.75,
          stops: [[9, 0.3], [12, 2], [22, 180]],
        },
        'circle-color': this.data.color,
        'circle-opacity': 0.3,
      }

      this.map.addLayer({
        id: this.id + 1,
        type: 'circle',
        source: this.id,
        paint: Object.assign(defaultPaint, this.data.paint || {}),
      })
    }

    if (type == 'heatmap') {
      this.layerNum = 2

      const defaultPaint = {
        // increase weight
        'heatmap-weight': 0.5,
        // increase intensity as zoom level increases
        'heatmap-intensity': {
          stops: [[9, 0.2], [10, 0.2], [11, 0.4], [15, 0.8]],
        },
        // assign color values be applied to points depending on their density
        'heatmap-color': [
          'interpolate',
          ['linear'],
          ['heatmap-density'],
          0,
          'rgba(236,222,239,0)',
          0.2,
          'rgb(208,209,230)',
          0.4,
          'rgb(166,189,219)',
          0.6,
          'rgb(103,169,207)',
          0.8,
          'rgb(28,144,153)',
        ],
        // increase radius as zoom increases
        'heatmap-radius': {
          // stops: [[11, 15], [15, 20]],
          stops: [[9, 1], [12, 25], [14, 50], [15, 100]],
        },
        // decrease opacity to transition into the circle layer
        'heatmap-opacity': {
          default: 0.15,
          // stops: [[14, 0.15], [15, 0]],
          stops: [[9, 0], [10, 0.15], [13, 0.25], [14, 0.2], [15, 0]],
        },
      }

      this.map.addLayer(
        {
          id: this.id + 1,
          type: 'heatmap',
          source: this.id,
          maxzoom: 15,
          minzoom: 9,
          paint: Object.assign(defaultPaint, this.data.paint || {}),
        },
        'waterway-label'
      )

      this.map.addLayer(
        {
          id: this.id + 2,
          type: 'circle',
          source: this.id,
          minzoom: 13,
          paint: {
            'circle-radius': {
              base: 1.75,
              stops: [[13, 2], [16, 10], [22, 180]],
            },
            'circle-color': this.data.color,
            // Transition from heatmap to circle layer by zoom level
            'circle-opacity': {
              stops: [[13, 0], [16, 0.6], [20, 0.8]],
            },
          },
        },
        'waterway-label'
      )
    }
    this.setLayerShow(show)
  },
  beforeDestroy() {
    for (let i = 1; i <= this.layerNum; i++) {
      this.map.removeLayer(this.id + i)
    }
    this.map.removeSource(this.id)
  },
  methods: {
    setLayerShow(show) {
      const visibility = show ? 'visible' : 'none'
      for (let i = 1; i <= this.layerNum; i++) {
        this.map.setLayoutProperty(this.id + i, 'visibility', visibility)
      }
    },
    refresh() {
      this.map.getSource(this.id).setData(this.data)

      const type = this.data.mapType
      const color = this.data.color
      const colorProperty = `${type}-color`

      if (type == 'heatmap') return

      for (let i = 1; i <= this.layerNum; i++) {
        this.map.setPaintProperty(this.id + i, colorProperty, color)
      }
    },
  },
}
</script>

<style lang="scss" scoped>
</style>
