<template>
  <div id="theCover">
    <div id="header">
      <h1 id="title"><i class="fa fa-map-o"/> VELOTROPOLIS <i class="fa fa-bicycle"/></h1>
    </div>
    

    <div id="legends" :style="{height: `${legendNum * 55 + 13}px`}">
      <span v-show="!legendNum" class="noVisibleLayer"> 
        <p><b>無 可視圖層</b> No Visible Layer</p> 
      </span>
      <div>
        <transition-group name="list-fade">
          <p v-for="(legend, key) in layerLegends" :key="key" class="legend">
            <span :style="{ backgroundColor: legend.color }" class="color"/>
            <span class="name">
              <strong class="zh">{{ legend.zh }}</strong>
              <br>
              <span class="en">{{ legend.en }}</span>
            </span>
          </p>
        </transition-group>
      </div>
      <div>
        <transition-group name="list-fade">
          <p v-for="(legend, key) in routingLegends" :key="key" class="legend">
            <span :style="{ backgroundColor: legend.color }" class="color"/>
            <span class="name">
              <strong class="zh">{{ legend.zh }}</strong>
              <br>
              <span class="en">{{ legend.en }}</span>
            </span>
          </p>
        </transition-group>
      </div>
    </div>

    <div id="source">
      <p>資料來源：自行車道資料由台北市政府提供。Data source: Bike Lane data are provided by Taipei City government.</p>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'

export default {
  name: 'TheCover',
  data() {
    return {}
  },
  computed: {
    ...mapGetters(['visibleLayers', 'visibleRouting']),
    layerLegends() {
      return this.visible2legend(this.visibleLayers)
    },
    routingLegends() {
      return this.visible2legend(this.visibleRouting)
    },
    legendNum() {
      return this.routingLegends.length + this.layerLegends.length
    },
  },
  methods: {
    visible2legend(v) {
      return v.map(l => ({
        color: l.color,
        zh: l.name.zh,
        en: l.name.en,
      }))
    },
  },
}
</script>

<style lang="scss" scoped>
#theCover {
  width: 100%;
  height: 100%;
  position: fixed;
  z-index: 100;
  pointer-events: none; // instructs the mouse event to go "through" the element.
}

#header {
  text-align: center;
  #title {
    // color: red;
    // margin-left: 5%;
    color: white;
    text-shadow: rgba(0, 0, 0, 0.8) 1px 1px 5px;

    margin-top: 50px;
    letter-spacing: 2px;
    // font-size: 44px;
    // font-size: 72px;
    font-size: 58px;
    // font-weight: 900;
  }
}

#legends {
  position: absolute;
  right: 0px;
  bottom: 10px;
  margin: 20px 15px;
  padding: 5px 20px;
  min-width: 210px;
  // min-height: 45px;
  min-height: fit-content;
  background-color: rgba(255, 255, 255, 0.65);
  font-size: 13px;
  border-radius: 3px;
  transition: height 0.7s;

  .noVisibleLayer {
    text-align: center;
  }

  .legend {
    display: flex;
    align-items: center;
    margin-left: 5px;

    > {
      display: inline-block;
    }

    $c: 20px;
    .color {
      margin-right: 15px;
      border-radius: $c;
      box-shadow: 0px 0pc 15px 2px #ccc;
      width: $c;
      height: $c;
    }

    .name {
      line-height: 1.5;
      font-size: 14px;
      .zh {
        letter-spacing: 0.8px;
      }
    }
  }
}

#source {
  position: absolute;
  line-height: 0.8;
  font-size: 10px;
  bottom: 0px;
  padding: 0px 15px;
  color: white;
  background-color: hsla(0, 0%, 0%, 0.6);
}

.list-fade-item {
  transition: all 1.5s;
}
.list-fade-enter,
.list-fade-leave-to {
  opacity: 0;
}
.list-fade-leave-active {
  position: absolute;
}

.list-fade-move {
  transition: all 1s;
}
</style>
