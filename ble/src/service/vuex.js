import Vue from 'vue'
import Vuex from 'vuex'
import update from 'immutability-helper'

Vue.use(Vuex)

import * as layers from '@/assets/layers'
import * as routing from '@/assets/routing'

const store = new Vuex.Store({
  state: {
    layers,
    routing,
    period: ['6', '8'],
  },
  mutations: {
    setLayerShow: (state, { layerKey, show }) => {
      console.log('[vuex] setLayerShow()', { layerKey, show })
      state.layers = update(state.layers, {
        [layerKey]: {
          show: {
            $set: show,
          },
        },
      })
    },
    setRoutingShow: (state, { routingKey, show }) => {
      console.log('[vuex] setRoutingShow()', { routingKey, show })
      state.routing = update(state.routing, {
        [routingKey]: {
          show: {
            $set: show,
          },
        },
      })
    },
    setLayerColor: (state, { layerKey, color }) => {
      console.log('[vuex] setLayerColor()', { layerKey, color })
      state.layers = update(state.layers, {
        [layerKey]: {
          color: {
            $set: color,
          },
        },
      })
    },
    setRoutingColor: (state, { routingKey, color }) => {
      console.log('[vuex] setRoutingColor()', { routingKey, color })
      state.routing = update(state.routing, {
        [routingKey]: {
          color: {
            $set: color,
          },
        },
      })
    },
    setPeriod: (state, { period }) => {
      console.log('[vuex] setPeriod()', { period })
      state.period = period
    },
  },
  getters: {
    visibleLayers: state => {
      return Object.values(state.layers).filter(l => l.show)
    },
    visibleRouting: state => {
      return Object.values(state.routing).filter(r => r.show)
    },
  },
})

export default store
